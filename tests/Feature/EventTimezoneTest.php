<?php

namespace Tests\Feature;

use App\Models\NewsEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventTimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_event_time_is_read_as_india_time_and_stored_in_utc(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.news.store'), $this->event('2026-12-01T10:00'))
            ->assertSessionHasNoErrors();

        $event = NewsEvent::firstOrFail();
        $this->assertSame('2026-12-01 04:30:00', $event->event_date->utc()->format('Y-m-d H:i:s'));

        // Edit form is prefilled back in India time.
        $this->actingAs($admin)->get(route('admin.news.edit', $event->id))
            ->assertInertia(fn (Assert $page) => $page->where('item.event_date_local', '2026-12-01T10:00'));

        // Saving the edit unchanged keeps the same moment.
        $this->actingAs($admin)->put(route('admin.news.update', $event->id), $this->event('2026-12-01T10:00'))
            ->assertSessionHasNoErrors();
        $this->assertSame('2026-12-01 04:30:00', $event->fresh()->event_date->utc()->format('Y-m-d H:i:s'));
    }

    public function test_homepage_upcoming_events_use_the_real_time(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.news.store'), $this->event('2026-12-01T10:00', 'Morning meet'));
        $this->actingAs($admin)->post(route('admin.news.store'), $this->event('2026-12-01T12:00', 'Noon meet'));
        auth()->logout();

        // 10:30 IST = 05:00 UTC: the 10:00 IST event is over, the 12:00 IST one is upcoming.
        Carbon::setTestNow(Carbon::parse('2026-12-01 05:00:00', 'UTC'));

        $this->get(route('welcome'))->assertInertia(fn (Assert $page) => $page
            ->has('upcomingEvents', 1)
            ->where('upcomingEvents.0.title', 'Noon meet'));

        $this->get(route('welcome'))->assertInertia(fn (Assert $page) => $page->where('displayTimeZone', 'Asia/Kolkata'));
    }

    public function test_migration_converts_existing_india_wall_clock_dates_to_utc_and_back(): void
    {
        $id = DB::table('news_events')->insertGetId([
            'title' => 'Old event', 'content' => 'x', 'type' => 'event', 'is_published' => true,
            'event_date' => '2026-12-01 10:00:00', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $migration = require database_path('migrations/2026_11_10_000001_convert_event_dates_to_utc.php');

        $migration->up();
        $this->assertSame('2026-12-01 04:30:00', (string) DB::table('news_events')->where('id', $id)->value('event_date'));

        $migration->down();
        $this->assertSame('2026-12-01 10:00:00', (string) DB::table('news_events')->where('id', $id)->value('event_date'));
    }

    private function event(string $when, string $title = 'Samaj meet'): array
    {
        return [
            'title' => $title,
            'content' => 'Community gathering',
            'type' => 'event',
            'event_date' => $when,
            'event_location' => 'Patna',
            'is_published' => true,
        ];
    }
}
