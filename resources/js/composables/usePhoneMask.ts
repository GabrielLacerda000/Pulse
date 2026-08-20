const phoneMaskOptions = { mask: ['(##) ####-####', '(##) #####-####'] };

function formatPhoneDisplay(phone: string | null | undefined): string {
    if (!phone) {
        return '';
    }

    let digits = phone.replace(/\D/g, '');

    if (digits.length > 11 && digits.startsWith('55')) {
        digits = digits.slice(2);
    }

    if (digits.length === 11) {
        return digits.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }

    if (digits.length === 10) {
        return digits.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    }

    return phone;
}

function stripPhoneDigits(phone: string | null | undefined): string {
    return (phone ?? '').replace(/\D/g, '');
}

export function usePhoneMask() {
    return { phoneMaskOptions, formatPhoneDisplay, stripPhoneDigits };
}
