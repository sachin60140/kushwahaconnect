import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * WhatsApp "click to chat" links with a prefilled Hindi welcome message.
 *
 * The sender's details (name, phone, email, WhatsApp group) come from
 * Admin → Settings (shared `websiteSettings` prop), so editing Settings updates
 * every message; nothing personal is hard-coded here.
 */

/**
 * Normalise a stored phone number to wa.me format (country code, digits only).
 * Bare 10-digit numbers are treated as Indian (+91). Returns null when unusable.
 */
export function toWhatsAppNumber(raw) {
    const digits = String(raw ?? '').replace(/\D/g, '');

    if (digits.length === 10) {
        return `91${digits}`;
    }

    if (digits.length === 11 && digits.startsWith('0')) {
        return `91${digits.slice(1)}`;
    }

    if (digits.length >= 11 && digits.length <= 15 && !digits.startsWith('0')) {
        return digits;
    }

    return null;
}

export function whatsAppLink(number, message) {
    return number ? `https://wa.me/${number}?text=${encodeURIComponent(message)}` : null;
}

export function useWhatsAppWelcome() {
    const page = usePage();
    const settings = computed(() => page.props.websiteSettings || {});

    const siteUrl = () => {
        try {
            return route('welcome');
        } catch {
            return window.location.origin;
        }
    };

    const senderBlock = () => {
        const s = settings.value;
        const lines = [];

        if (s.whatsapp_group_link) {
            lines.push(`हमारे WhatsApp समूह से जुड़ें: ${s.whatsapp_group_link}`, '');
        }

        lines.push('किसी भी जानकारी या सहायता के लिए संपर्क करें:');
        lines.push(s.founder_name || 'KushwahaConnect टीम');

        if (s.contact_mobile) {
            lines.push(`मोबाइल: ${s.contact_mobile}`);
        }

        if (s.contact_email) {
            lines.push(`ईमेल: ${s.contact_email}`);
        }

        lines.push(`वेबसाइट: ${siteUrl()}`, '', 'सादर,', 'KushwahaConnect परिवार');

        return lines.join('\n');
    };

    const greeting = (name) => `नमस्ते ${String(name || '').trim() || 'साथी'} जी 🙏`;

    const memberMessage = (member) => [
        greeting(member?.full_name),
        '',
        'KushwahaConnect परिवार में आपका हार्दिक स्वागत है! आपका सदस्यता पंजीकरण सफलतापूर्वक पूरा हो गया है।',
        '',
        'अब आप समाज के सदस्यों से जुड़ सकते हैं और सहायता, रोज़गार, शिक्षा, व्यवसाय तथा कार्यक्रमों की जानकारी प्राप्त कर सकते हैं। कृपया अपनी प्रोफ़ाइल पूरी रखें ताकि समाज के लोग आपसे आसानी से जुड़ सकें।',
        '',
        senderBlock(),
    ].join('\n');

    const volunteerMessage = (volunteer) => [
        greeting(volunteer?.name),
        '',
        'KushwahaConnect में स्वयंसेवक के रूप में जुड़ने के लिए आपका हार्दिक स्वागत और धन्यवाद! समाज की सेवा में आपका सहयोग बहुत मूल्यवान है।',
        '',
        volunteer?.district
            ? `हम ${volunteer.district} क्षेत्र में ज़रूरतमंद लोगों की मदद और सामाजिक कार्यों के लिए आपसे संपर्क करते रहेंगे।`
            : 'हम ज़रूरतमंद लोगों की मदद और सामाजिक कार्यों के लिए आपसे संपर्क करते रहेंगे।',
        '',
        senderBlock(),
    ].join('\n');

    /** Prefer the WhatsApp number, fall back to the mobile number. */
    const memberLink = (member) =>
        whatsAppLink(toWhatsAppNumber(member?.whatsapp) || toWhatsAppNumber(member?.mobile), memberMessage(member));

    const volunteerLink = (volunteer) =>
        whatsAppLink(toWhatsAppNumber(volunteer?.whatsapp) || toWhatsAppNumber(volunteer?.mobile), volunteerMessage(volunteer));

    return { memberMessage, volunteerMessage, memberLink, volunteerLink };
}
