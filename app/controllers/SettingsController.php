<?php
declare(strict_types=1);

final class SettingsController
{
    /** key => [label, type, rules, tab, options] */
    public static function schema(): array
    {
        return [
            'school_name' => ['School Name', 'text', 'required|max:150', 'general'],
            'school_short_name' => ['Short Name (sidebar)', 'text', 'max:40', 'general'],
            'school_motto' => ['Motto / Tagline', 'text', 'max:150', 'general'],
            'school_reg_no' => ['Registration / EMIS No.', 'text', 'max:50', 'general'],
            'principal_name' => ['Principal Name', 'text', 'max:120', 'general'],
            'school_phone' => ['Contact Number', 'text', 'max:50', 'general'],
            'school_email' => ['Email Address', 'email', 'max:150', 'general'],
            'school_website' => ['Website', 'text', 'max:150', 'general'],
            'school_address' => ['Address', 'textarea', 'max:255', 'general'],
            'school_start_time' => ['School Start Time', 'time', 'time', 'academic'],
            'school_end_time' => ['School End Time', 'time', 'time', 'academic'],
            'date_format' => ['Date Format', 'select', 'required', 'academic', ['d-M-Y' => '10-Nov-2026', 'd/m/Y' => '10/11/2026', 'Y-m-d' => '2026-11-10', 'd M, Y' => '10 Nov, 2026']],
            'default_lang' => ['Default Language', 'select', 'required', 'academic', ['en' => 'English', 'ur' => 'اردو (Urdu)']],
            'currency_code' => ['Currency Code', 'text', 'required|max:5', 'fees'],
            'currency_symbol' => ['Currency Symbol', 'text', 'required|max:5', 'fees'],
            'currency_decimals' => ['Decimal Places', 'select', 'required', 'fees', ['0' => '0 (Rs. 1,500)', '2' => '2 (Rs. 1,500.00)']],
            'fee_due_day' => ['Default Due Day of Month', 'number', 'required|integer|min:1|max:28', 'fees'],
            'late_fine_type' => ['Late Fine Type', 'select', 'required', 'fees', ['fixed' => 'Fixed amount once overdue', 'per_day' => 'Per day after due date']],
            'late_fine_amount' => ['Late Fine Amount', 'number', 'required|numeric|min:0', 'fees'],
            'late_fine_max' => ['Maximum Fine (0 = no limit)', 'number', 'required|numeric|min:0', 'fees'],
            'reg_prefix' => ['Registration No. Prefix', 'text', 'required|max:10', 'fees'],
            'invoice_prefix' => ['Invoice No. Prefix', 'text', 'required|max:10', 'fees'],
            'receipt_prefix' => ['Receipt No. Prefix', 'text', 'required|max:10', 'fees'],
            'teacher_prefix' => ['Teacher ID Prefix', 'text', 'required|max:10', 'fees'],
            'staff_prefix' => ['Staff ID Prefix', 'text', 'required|max:10', 'fees'],
            'pass_percentage' => ['Overall Pass Percentage', 'number', 'required|numeric|min:0|max:100', 'grading'],
            'pass_each_subject' => ['Must pass every subject', 'checkbox', '', 'grading'],
            'receipt_copies' => ['Copies per receipt / challan', 'select', 'required', 'receipt', ['1' => '1', '2' => '2', '3' => '3']],
            'challan_bank' => ['Bank details on challan', 'text', 'max:200', 'receipt'],
            'receipt_note' => ['Receipt footer note', 'textarea', 'max:300', 'receipt'],
            'idcard_color' => ['ID Card Colour', 'color', 'max:7', 'idcard'],
            'idcard_validity' => ['ID Card Valid Till (text)', 'text', 'max:30', 'idcard'],
            'idcard_note' => ['ID Card Footer Note', 'text', 'max:100', 'idcard'],
            'library_loan_days' => ['Library loan period (days)', 'number', 'required|integer|min:1|max:120', 'idcard'],
            'library_fine_per_day' => ['Library fine per day', 'number', 'required|numeric|min:0', 'idcard'],
            'max_upload_mb' => ['Max upload size (MB)', 'number', 'required|integer|min:1|max:50', 'idcard'],
            'notify_absent' => ['Notify parents when a student is absent', 'checkbox', '', 'notify'],
            'notify_fee_due' => ['Enable fee due reminders', 'checkbox', '', 'notify'],
            'notify_results' => ['Notify on result publication', 'checkbox', '', 'notify'],
            'email_enabled' => ['Send emails (PHP mail / server SMTP)', 'checkbox', '', 'notify'],
            'email_from' => ['From email address', 'email', 'max:150', 'notify'],
            'sms_enabled' => ['Send SMS via HTTP gateway', 'checkbox', '', 'notify'],
            'sms_api_url' => ['SMS gateway URL ({to} and {message} placeholders)', 'text', 'max:500', 'notify'],
            'backup_keep' => ['Backups to keep on server', 'number', 'required|integer|min:1|max:100', 'backup'],
        ];
    }

    public function index(): void
    {
        authorize('settings.manage');
        view('settings/index', ['schema' => self::schema(), 'title' => t('School Settings')]);
    }

    public function save(): void
    {
        authorize('settings.manage');
        $tab = (string)input('tab', 'general');
        $schema = array_filter(self::schema(), fn($s) => $s[3] === $tab);
        $data = [];
        $rules = [];
        $labels = [];
        foreach ($schema as $k => $s) {
            $data[$k] = $s[1] === 'checkbox' ? (isset($_POST[$k]) ? '1' : '0') : trim((string)($_POST[$k] ?? ''));
            $rules[$k] = $s[2] . ($s[1] === 'email' ? '|email' : '') . (in_array($s[1], ['number'], true) && !str_contains($s[2], 'numeric') && !str_contains($s[2], 'integer') ? '|numeric' : '');
            $labels[$k] = $s[0];
            if ($s[1] === 'select' && !array_key_exists($data[$k], $s[4])) {
                throw new ValidationException([$k => $s[0] . ' is invalid.']);
            }
        }
        validate_or_fail($data, array_map(fn($r) => trim($r, '|'), $rules), $labels);
        if (isset($data['sms_api_url']) && $data['sms_api_url'] !== '' && !preg_match('#^https?://#i', $data['sms_api_url'])) {
            throw new ValidationException(['sms_api_url' => 'SMS gateway URL must start with http:// or https://']);
        }
        if (isset($data['idcard_color']) && !preg_match('/^#[0-9a-fA-F]{6}$/', $data['idcard_color'])) {
            $data['idcard_color'] = '#1e40af';
        }
        DB::tx(function () use ($data, $tab) {
            foreach ($data as $k => $v) {
                save_setting($k, $v);
            }
            if ($tab === 'academic') {
                $days = array_values(array_intersect(array_map('intval', (array)($_POST['working_days'] ?? [])), range(1, 7)));
                if (!$days) {
                    throw new ValidationException(['working_days' => 'Select at least one working day.']);
                }
                save_setting('working_days', implode(',', $days));
            }
            if ($tab === 'general') {
                if ($logo = upload_file('school_logo', 'settings', 'image')) {
                    delete_upload(setting('school_logo'));
                    save_setting('school_logo', $logo);
                } elseif (input('remove_logo')) {
                    delete_upload(setting('school_logo'));
                    save_setting('school_logo', '');
                }
            }
        });
        audit('update', 'settings', $tab, json_encode(array_diff_key($data, ['sms_api_url' => 1]), JSON_UNESCAPED_UNICODE));
        flash('success', t('Settings saved.'));
        redirect(url('settings') . '#' . $tab);
    }

    /** Send a test SMS / email */
    public function test_message(): void
    {
        authorize('settings.manage');
        $to = trim((string)input('to'));
        $ok = str_contains($to, '@') ? send_email($to, 'Test email from ' . setting('school_name'), 'This is a test message from your School ERP.') : send_sms($to, 'Test SMS from ' . setting('school_name'));
        json_out(['ok' => $ok, 'message' => $ok ? t('Test message sent.') : t('Sending failed. Check that the channel is enabled and configured, and see the Message Log.')]);
    }
}
