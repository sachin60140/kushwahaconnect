// Visible labels for fixed option values. The `value` is what the server stores and
// validates, so it never changes; only the label shown to the visitor is translated.
// Every labelKey must exist in both the en and hi blocks of dictionary.js.

export const HELP_CATEGORIES = [
    { value: 'Education Help', labelKey: 'help_cat_education' },
    { value: 'Job Help', labelKey: 'help_cat_job' },
    { value: 'Business Help', labelKey: 'help_cat_business' },
    { value: 'Medical Help', labelKey: 'help_cat_medical' },
    { value: 'Emergency Help', labelKey: 'help_cat_emergency' },
    { value: 'Government Scheme Help', labelKey: 'help_cat_scheme' },
    { value: 'Legal Guidance', labelKey: 'help_cat_legal' },
    { value: 'Other', labelKey: 'help_cat_other' },
];

export const HELP_URGENCIES = [
    { value: 'Normal', labelKey: 'help_urgency_normal' },
    { value: 'Urgent', labelKey: 'help_urgency_urgent' },
    { value: 'Emergency', labelKey: 'help_urgency_emergency' },
];

export const VOLUNTEER_AREAS = [
    { value: 'education_support', labelKey: 'vol_area_education' },
    { value: 'job_support', labelKey: 'vol_area_job' },
    { value: 'medical_support', labelKey: 'vol_area_medical' },
    { value: 'event_support', labelKey: 'vol_area_event' },
    { value: 'social_media_support', labelKey: 'vol_area_social_media' },
    { value: 'blood_donor', labelKey: 'vol_area_blood_donor' },
];

export const VOLUNTEER_TIMES = [
    { value: 'Weekends Only', labelKey: 'vol_time_weekends' },
    { value: 'Evening Hours', labelKey: 'vol_time_evening' },
    { value: 'Flexible / As Needed', labelKey: 'vol_time_flexible' },
    { value: 'Full Time (During Crisis)', labelKey: 'vol_time_full_time' },
];

// Matches BusinessListingController::$categories.
export const BUSINESS_CATEGORIES = [
    { value: 'Agriculture & Farming', labelKey: 'biz_cat_agriculture' },
    { value: 'Arts & Entertainment', labelKey: 'biz_cat_arts' },
    { value: 'Beauty & Personal Care', labelKey: 'biz_cat_beauty' },
    { value: 'Construction & Real Estate', labelKey: 'biz_cat_construction' },
    { value: 'Education & Training', labelKey: 'biz_cat_education' },
    { value: 'Finance & Insurance', labelKey: 'biz_cat_finance' },
    { value: 'Food & Beverage', labelKey: 'biz_cat_food' },
    { value: 'Healthcare & Medical', labelKey: 'biz_cat_healthcare' },
    { value: 'IT & Technology', labelKey: 'biz_cat_it' },
    { value: 'Manufacturing', labelKey: 'biz_cat_manufacturing' },
    { value: 'Media & Communications', labelKey: 'biz_cat_media' },
    { value: 'Professional Services', labelKey: 'biz_cat_professional' },
    { value: 'Retail & Wholesale', labelKey: 'biz_cat_retail' },
    { value: 'Transportation & Logistics', labelKey: 'biz_cat_transport' },
    { value: 'Other', labelKey: 'biz_cat_other' },
];

export const GENDERS = [
    { value: 'Male', labelKey: 'join_gender_male' },
    { value: 'Female', labelKey: 'join_gender_female' },
    { value: 'Other', labelKey: 'join_gender_other' },
];

export const MAT_PROFILE_FOR = [
    { value: 'Self', labelKey: 'mat_for_self' },
    { value: 'Son', labelKey: 'mat_for_son' },
    { value: 'Daughter', labelKey: 'mat_for_daughter' },
    { value: 'Brother', labelKey: 'mat_for_brother' },
    { value: 'Sister', labelKey: 'mat_for_sister' },
    { value: 'Relative', labelKey: 'mat_for_relative' },
    { value: 'Friend', labelKey: 'mat_for_friend' },
];

export const MAT_GENDERS = [
    { value: 'Male', labelKey: 'mat_gender_groom' },
    { value: 'Female', labelKey: 'mat_gender_bride' },
];

export const MAT_MARITAL_STATUSES = [
    { value: 'Never Married', labelKey: 'mat_marital_never' },
    { value: 'Awaiting Divorce', labelKey: 'mat_marital_awaiting' },
    { value: 'Divorced', labelKey: 'mat_marital_divorced' },
    { value: 'Widowed', labelKey: 'mat_marital_widowed' },
    { value: 'Annulled', labelKey: 'mat_marital_annulled' },
];

export const MAT_MANGLIK = [
    { value: 'No', labelKey: 'mat_manglik_no' },
    { value: 'Yes', labelKey: 'mat_manglik_yes' },
    { value: "Don't Know", labelKey: 'mat_manglik_dont_know' },
    { value: 'Anshik', labelKey: 'mat_manglik_anshik' },
];

export const MAT_FAMILY_TYPES = [
    { value: 'Joint', labelKey: 'mat_family_joint' },
    { value: 'Nuclear', labelKey: 'mat_family_nuclear' },
    { value: 'Others', labelKey: 'mat_family_others' },
];

// users.status and business listing statuses.
export const ACCOUNT_STATUSES = [
    { value: 'profile_incomplete', labelKey: 'dash_status_profile_incomplete' },
    { value: 'pending_approval', labelKey: 'dash_status_pending_approval' },
    { value: 'pending', labelKey: 'dash_status_pending' },
    { value: 'approved', labelKey: 'dash_status_approved' },
    { value: 'rejected', labelKey: 'dash_status_rejected' },
    { value: 'suspended', labelKey: 'dash_status_suspended' },
    { value: 'blocked', labelKey: 'dash_status_blocked' },
];

/** Translated label for a stored value; unknown values are shown as they are. */
export const optionLabel = (__, options, value) => {
    const option = options.find((item) => item.value === value);
    return option ? __(option.labelKey) : value;
};

/** State / district name in the visitor's language (Hindi name when there is one). */
export const placeName = (locale, item) => {
    if (!item) return '';
    return locale === 'hi' && item.name_hi ? item.name_hi : item.name_en;
};

/** Laravel paginator labels ("&laquo; Previous", "Next &raquo;") in the visitor's language. */
export const paginationLabel = (__, label) => String(label)
    .replace('Previous', __('common_previous'))
    .replace('Next', __('common_next'));
