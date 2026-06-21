<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobPosting;
use App\Models\EducationResource;
use App\Models\MedicalResource;
use App\Models\Volunteer;
use App\Models\HelpRequest;
use App\Models\Gallery;

class DummyContentSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Jobs
        JobPosting::create([
            'user_id' => 1, // Admin user
            'title' => 'Software Developer',
            'company' => 'Tech Solutions Ltd.',
            'location' => 'Patna, Bihar',
            'description' => 'Looking for an experienced software developer with PHP and Vue.js experience.',
            'requirements' => '- 2+ years of experience\n- B.Tech/MCA\n- Knowledge of Laravel',
            'salary_range' => '₹4,00,000 - ₹8,00,000 PA',
            'contact_email' => 'hr@techsolutions.com',
            'status' => 'approved'
        ]);

        JobPosting::create([
            'user_id' => 1,
            'title' => 'Accountant',
            'company' => 'Gupta Associates',
            'location' => 'Ranchi, Jharkhand',
            'description' => 'Need a part-time accountant to manage daily ledger and GST filing.',
            'requirements' => 'B.Com graduate with tally knowledge.',
            'contact_email' => 'jobs@gupta.com',
            'status' => 'approved'
        ]);

        // 2. Education
        EducationResource::create([
            'title' => 'Kushwaha Merit Scholarship 2026',
            'category' => 'Scholarship',
            'description' => 'A special scholarship for meritorious students of the Kushwaha Samaj scoring above 90% in 12th boards.',
            'link_url' => 'https://example.com/scholarship',
            'deadline_date' => now()->addMonths(2)->format('Y-m-d')
        ]);

        EducationResource::create([
            'title' => 'Free Coding Bootcamp for Community Youth',
            'category' => 'Course',
            'description' => 'Join our free online bootcamp to learn web development. Open exclusively for community members.',
            'link_url' => 'https://example.com/bootcamp',
        ]);

        // 3. Medical
        MedicalResource::create([
            'title' => 'Free Health Checkup Camp',
            'type' => 'hospital',
            'description' => 'A free general health checkup camp organized at Gandhi Maidan. Blood test and consultation available.',
            'contact_info' => 'Dr. A.K. Kushwaha - 9988776655',
            'urgency' => 'Normal',
            'is_published' => true
        ]);

        MedicalResource::create([
            'title' => 'Need O+ Blood in Lucknow',
            'type' => 'blood_request',
            'description' => 'We are organizing a blood donation drive. Your blood can save a life!',
            'contact_info' => 'Ravi Prakash - 9123456789',
            'blood_group' => 'O+',
            'urgency' => 'Emergency',
            'is_published' => true
        ]);

        // 4. Help Requests
        HelpRequest::create([
            'user_id' => null,
            'name' => 'Sita Devi',
            'mobile' => '9876501234',
            'state' => 'Bihar',
            'district' => 'Gaya',
            'city' => 'Gaya',
            'category' => 'Medical Help',
            'urgency' => 'Emergency',
            'title' => 'Need O+ Blood Donor in Gaya immediately',
            'description' => 'My husband is undergoing surgery tomorrow at City Hospital Gaya. We urgently need 2 units of O+ blood.',
            'status' => 'approved'
        ]);

        HelpRequest::create([
            'user_id' => 1,
            'name' => 'Rahul Kushwaha',
            'mobile' => '9001122334',
            'state' => 'Uttar Pradesh',
            'district' => 'Varanasi',
            'city' => 'Varanasi',
            'category' => 'Education Help',
            'urgency' => 'Normal',
            'title' => 'Guidance for UPSC Preparation',
            'description' => 'I am a recent graduate and want to start preparing for UPSC. Need guidance from someone who has cleared or prepared for the exam.',
            'status' => 'approved'
        ]);

        // 5. Volunteers
        Volunteer::create([
            'user_id' => 1,
            'name' => 'Suresh Singh',
            'email' => 'suresh@example.com',
            'mobile' => '9898989898',
            'state' => 'Madhya Pradesh',
            'district' => 'Bhopal',
            'city' => 'Bhopal',
            'skills' => 'Event Management, Teaching',
            'available_time' => 'Weekends',
            'help_areas' => '["Education", "Events"]',
            'status' => 'approved'
        ]);

        // 6. Gallery
        Gallery::create([
            'title' => 'Community Annual Meetup 2025',
            'description' => 'Photos from our grand annual meetup held last year.',
            'is_published' => true
        ]);

        Gallery::create([
            'title' => 'Youth Education Seminar',
            'description' => 'Career guidance seminar for 12th pass students.',
            'is_published' => true
        ]);
    }
}
