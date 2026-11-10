import { usePage } from '@inertiajs/vue3';

// Zone for showing stored (UTC) times such as event dates: config('app.display_timezone').
export const displayTimeZone = () => usePage().props.displayTimeZone || 'Asia/Kolkata';
