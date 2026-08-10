<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\State;
use App\Models\District;
use App\Models\MemberProfile;
use App\Models\ContactEnquiry;
use App\Models\DataDeletionRequest;
use App\Models\User;
use App\Support\ImageUpload;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class PublicController extends Controller
{
    public function about()
    {
        return Inertia::render('About');
    }

    public function privacy()
    {
        return Inertia::render('Static', [
            'title' => 'Privacy Policy',
            'content' => $this->privacyPolicyContent(),
        ]);
    }

    public function terms()
    {
        return Inertia::render('Static', [
            'title' => 'Terms & Conditions',
            'content' => $this->termsContent(),
        ]);
    }

    public function dataDeletion()
    {
        return Inertia::render('DataDeletion');
    }

    public function storeDataDeletion(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'reason' => 'nullable|string|max:2000',
        ]);

        DataDeletionRequest::create($validated);

        return redirect()->back()->with('success', 'Your data deletion request has been submitted. Our team will verify your details and process the request.');
    }

    public function join()
    {
        return Inertia::render('Join', [
            'states' => State::where('status', true)->get()
        ]);
    }

    public function storeJoin(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'father_husband_name' => 'nullable|string|max:255',
            'gender' => 'required|string',
            'dob' => 'nullable|date',
            'mobile' => ['required', 'string', 'max:20', Rule::unique(User::class, 'mobile')],
            'whatsapp' => 'nullable|string|max:20',
            'email' => ['nullable', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'state_id' => 'required|exists:states,id',
            'district_id' => 'required|exists:districts,id',
            'city_village' => 'required|string|max:255',
            'pin_code' => 'required|string|max:20',
            'current_address' => 'required|string',
            'native_place' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'skills' => 'nullable|string|max:255',
            'gotra' => 'nullable|string|max:255',
            'social_work_interest' => 'nullable|string|max:255',
            'volunteer_interest' => 'boolean',
            'join_district_team' => 'boolean',
            'facebook' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'is_public' => 'boolean',
            'show_mobile' => 'boolean',
            'allow_whatsapp' => 'boolean',
            'consent' => 'accepted',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if ($request->hasFile('profile_photo')) {
            $validated['profile_photo'] = ImageUpload::storeProfilePhoto($request->file('profile_photo'), 'profile_photos');
        }

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'] ?? null,
                'mobile' => $validated['mobile'],
                'password' => Hash::make($validated['password']),
                'status' => 'approved',
            ]);

            $validated['user_id'] = $user->id;
            $validated['status'] = 'approved';

            // Remove password fields before creating member profile
            unset($validated['password']);
            unset($validated['password_confirmation']);

            MemberProfile::create($validated);

            return $user;
        });

        // Auto-login the user
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Registration completed successfully. Your profile is approved.');
    }

    public function matrimonial()
    {
        return Inertia::render('Matrimonial', [
            'states' => State::where('status', true)->get()
        ]);
    }

    public function storeMatrimonial(Request $request)
    {
        $validated = $request->validate([
            'profile_for' => 'required|string',
            'full_name' => 'required|string|max:255',
            'gender' => 'required|string',
            'dob' => 'required|date',
            'marital_status' => 'required|string',
            'mobile' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'current_city' => 'nullable|string|max:255',
            'education' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'photo' => 'nullable|image|max:2048',
            'id_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'consent' => 'accepted'
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = ImageUpload::storeProfilePhoto($request->file('photo'), 'matrimonial_photos', 'local');
        }

        if ($request->hasFile('id_proof')) {
            $validated['id_proof'] = $request->file('id_proof')->store('matrimonial_ids', 'local');
        }

        $validated['user_id'] = auth()->id();
        \App\Models\MatrimonialProfile::create($validated);

        return redirect()->back()->with('success', 'Matrimonial Profile submitted successfully.');
    }

    public function showMatrimonialPhoto(Request $request, $id)
    {
        $profile = \App\Models\MatrimonialProfile::with('user.privacySetting')->findOrFail($id);
        
        if (!$profile->photo) {
            abort(404);
        }

        $viewer = $request->user();
        $isOwner = $viewer && $profile->user_id === $viewer->id;
        $isAdmin = $viewer && in_array($viewer->role, ['admin', 'super_admin'], true);

        if (!$isOwner && !$isAdmin) {
            if ($profile->status !== 'approved' || $profile->hide_photo) {
                abort(404);
            }

            $visibility = $profile->user?->privacySetting?->matrimonial_photo_visibility ?? 'public';

            if ($visibility === 'hidden') {
                abort(404);
            }

            if ($visibility === 'accepted_interests_only') {
                $hasAcceptedConnection = $profile->user_id && \App\Models\Connection::where('status', 'accepted')
                    ->where(function ($query) use ($viewer, $profile) {
                        $query->where(function ($q) use ($viewer, $profile) {
                            $q->where('sender_id', $viewer->id)
                                ->where('receiver_id', $profile->user_id);
                        })->orWhere(function ($q) use ($viewer, $profile) {
                            $q->where('sender_id', $profile->user_id)
                                ->where('receiver_id', $viewer->id);
                        });
                    })
                    ->exists();

                if (!$hasAcceptedConnection) {
                    abort(404);
                }
            }
        }

        $path = Storage::disk('local')->path($profile->photo);

        if (!is_file($path)) {
            abort(404);
        }

        return response()->file($path);
    }

    public function contact()
    {
        return Inertia::render('Contact', [
            'states' => State::where('status', true)->get()
        ]);
    }

    public function storeContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'state_id' => 'nullable|exists:states,id',
            'district_id' => 'nullable|exists:districts,id',
            'message' => 'required|string',
            'purpose' => 'nullable|string|max:255'
        ]);

        ContactEnquiry::create($validated);

        return redirect()->back()->with('success', 'Message sent successfully.');
    }

    public function getDistricts(State $state)
    {
        return response()->json($state->districts()->where('status', true)->get());
    }
    public function directory(Request $request)
    {
        $query = MemberProfile::with(['state', 'district'])
            ->where('status', 'approved')
            ->where('is_public', true);

        if ($request->filled('search')) {
            $query->where('full_name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        if ($request->filled('occupation')) {
            $query->where('occupation', 'like', '%' . $request->occupation . '%');
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('skills')) {
            $query->where('skills', 'like', '%' . $request->skills . '%');
        }

        return Inertia::render('Directory', [
            'members' => $query->latest()->paginate(12),
            'states' => State::where('status', true)->get(),
            'filters' => $request->only(['search', 'state_id', 'district_id', 'occupation', 'gender', 'skills'])
        ]);
    }

    private function privacyPolicyContent(): string
    {
        return <<<'HTML'
<p class="text-sm font-bold text-orange-600">Last updated: 21 June 2026</p>
<p>KushwahaConnect.com is a digital community platform for Kushwaha Samaj. This Privacy Policy explains what information we collect, why we collect it, how we use it, and the choices available to members and visitors.</p>

<h2>Information We Collect</h2>
<p>When you join the community, submit a matrimonial profile, request help, register as a volunteer, submit a business listing, contact us, or use other platform features, we may collect the following information:</p>
<ul>
    <li>Name, mobile number, WhatsApp number, email address, gender, date of birth, state, district, city or village, and address details.</li>
    <li>Education, occupation, business, skills, social work interest, volunteer interest, and community-related details.</li>
    <li>Matrimonial details such as profile information, family details, preferences, photo, and identity proof where submitted.</li>
    <li>Messages, help requests, contact enquiries, volunteer applications, business listing details, and uploaded images or documents.</li>
    <li>Basic technical details such as browser/session information needed for account security and website operation.</li>
</ul>

<h2>How We Use Information</h2>
<p>We use submitted information to run community services, verify profiles, enable member discovery, support matrimonial and help workflows, publish approved public content, respond to enquiries, prevent misuse, and improve KushwahaConnect.</p>

<h2>Public Visibility</h2>
<p>Only permitted details may appear publicly. Community directory previews do not show full mobile numbers, email addresses, or exact addresses. Matrimonial visibility may depend on profile approval, privacy settings, and platform rules.</p>

<h2>Sharing of Information</h2>
<p>We do not sell personal data. Information may be shared only where needed for platform features, legal compliance, safety, verification, dispute handling, or with your consent. Publicly submitted listings, news, events, help requests, volunteer information, and approved profiles may be visible according to their settings.</p>

<h2>Data Security</h2>
<p>We use reasonable technical and administrative safeguards to protect submitted information. However, no website or digital platform can guarantee absolute security. Members should avoid sharing sensitive information publicly unless necessary.</p>

<h2>Your Choices</h2>
<p>You may update your account/profile details, change available privacy settings, or request deletion of your account/profile data. To request deletion, visit the <a href="/data-deletion">Data Deletion Request</a> page.</p>

<h2>Children and Family Profiles</h2>
<p>If a family member submits details on behalf of another person, they must have permission to do so. Users should not submit information they are not authorized to share.</p>

<h2>Changes to This Policy</h2>
<p>We may update this Privacy Policy when platform features, laws, or operating practices change. The updated version will be posted on this page.</p>

<h2>Contact</h2>
<p>For privacy questions, corrections, or support, please use the <a href="/contact">Contact</a> page or submit a request through the relevant platform form.</p>
HTML;
    }

    private function termsContent(): string
    {
        return <<<'HTML'
<p class="text-sm font-bold text-orange-600">Last updated: 21 June 2026</p>
<p>These Terms & Conditions govern your use of KushwahaConnect.com. By using the website, creating an account, submitting details, or accessing any service, you agree to follow these terms.</p>

<h2>Purpose of the Platform</h2>
<p>KushwahaConnect.com is a community platform for Kushwaha Samaj. It supports community profiles, directory discovery, education and career support, business networking, help requests, volunteering, events, news, gallery, and matrimonial services.</p>

<h2>User Responsibilities</h2>
<ul>
    <li>Submit true, accurate, and authorized information.</li>
    <li>Do not upload false, misleading, offensive, illegal, copyrighted, or harmful content.</li>
    <li>Do not misuse phone numbers, photos, documents, or profile data of other members.</li>
    <li>Do not harass, spam, threaten, impersonate, or exploit any member or visitor.</li>
    <li>Keep your login details secure and notify us if you suspect unauthorized access.</li>
</ul>

<h2>Profile Review and Approval</h2>
<p>Community member registrations are active after submission. Matrimonial profiles, business listings, news, events, help requests, volunteer registrations, and gallery content may require admin approval. KushwahaConnect may approve, reject, edit, hide, suspend, or remove content that violates platform rules or community safety expectations.</p>

<h2>Matrimonial Service</h2>
<p>The matrimonial section is intended to help families discover suitable profiles within the community. Users are responsible for verifying identity, family details, documents, compatibility, and any other information before making personal decisions. KushwahaConnect does not guarantee any match, marriage, relationship outcome, or background verification beyond platform review.</p>

<h2>Community Directory and Connections</h2>
<p>Directory visibility depends on approval and privacy settings. Members must use directory and connection features respectfully and only for genuine community purposes.</p>

<h2>Help, Volunteer, Business, and Public Content</h2>
<p>Help requests, volunteer details, business listings, events, news, success stories, and gallery items may be displayed publicly after approval. Users are responsible for the accuracy and legality of submitted content.</p>

<h2>No Professional Advice</h2>
<p>Information related to education, jobs, medical help, government schemes, business, or community support is shared for general assistance. It should not replace professional, legal, medical, financial, or government advice.</p>

<h2>Account or Content Removal</h2>
<p>We may suspend or remove accounts, profiles, listings, or content for misuse, false information, privacy violations, safety concerns, legal requests, or breach of these terms. You may request account or profile deletion through the <a href="/data-deletion">Data Deletion Request</a> page.</p>

<h2>Limitation of Liability</h2>
<p>KushwahaConnect is provided on an as-available basis. We are not liable for user-submitted information, offline interactions, decisions made by families or members, business dealings, job outcomes, help outcomes, technical downtime, or losses arising from misuse of the platform.</p>

<h2>Updates to Terms</h2>
<p>These terms may be updated as the platform grows. Continued use of KushwahaConnect after updates means you accept the revised terms.</p>

<h2>Contact</h2>
<p>For questions about these terms, please use the <a href="/contact">Contact</a> page.</p>
HTML;
    }
}
