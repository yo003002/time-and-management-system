<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],

            'breaks' => ['array'],
            'breaks.*.start' => [
                'nullable',
                'date_format:H:i',
                'required_with:breaks.*.end'
            ],
            'breaks.*.end' => [
                'nullable',
                'date_format:H:i',
                'required_with:breaks.*.start',
            ],

            'remark' => ['required', 'string'],
        ];
    }

    public function messages(): array{
        return [
            'clock_in.required'  => '出勤時間を入力してください',
            'clock_out.required' => '退勤時間を入力してください',
            'clock_in.date_format'  => '出勤時間の形式が正しくありません',
            'clock_out.date_format' => '退勤時間の形式が正しくありません',

            'breaks.*.start.date_format' => '休憩時間の形式が正しくありません',
            'breaks.*.end.date_format' => '休憩時間の形式が正しくありません',
            'breaks.*.start.required_with' => '休憩開始時間を入力してください',
            'breaks.*.end.required_with' => '休憩終了時間を入力してください',

            'remark.required' => '備考を記入してください',
        ];
    }

    public function attributes(): array
    {
        return [
            'clock_in' => '出勤時間',
            'clock_out' => '退勤時間',
            'breaks.*.start' => '休憩開始時間',
            'breaks.*.end' => '休憩終了時間',
            'remark' => '備考',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            $clockIn = $this->input('clock_in');
            $clockOut = $this->input('clock_out');

            // 出勤時間と退勤時間のチェック
            if ($clockIn && $clockOut) {
                if (strtotime($clockIn) >= strtotime($clockOut)) {
                    $validator->errors()->add(
                        'clock_out',
                        '出勤時間もしくは退勤時間が不適切な値です'
                    );
                }
            }

            // 休憩時間のチェック
            $breaks = $this->input('breaks', []);

            $clockInTime = $clockIn ? strtotime($clockIn) : null;
            $clockOutTime = $clockOut ? strtotime($clockOut) : null;

            foreach ($breaks as $i => $break) {
                if (!empty($break['start'])) {
                    $start = strtotime($break['start']);

                    if ($clockInTime && $start < $clockInTime) {
                        $validator->errors()->add(
                            "breaks.$i.start",
                            '休憩時間が不適切な値です'
                        );
                    }

                    if ($clockOutTime && $start > $clockOutTime) {
                        $validator->errors()->add(
                            "breaks.$i.start",
                            '休憩時間が不適切な値です'
                        );
                    }
                }

                if (!empty($break['end'])) {
                    $end = strtotime($break['end']);

                    if ($clockOutTime && $end > $clockOutTime) {
                        $validator->errors()->add(
                            "breaks.$i.end",
                            '休憩時間もしくは退勤時間が不適切な値です'
                        );
                    }
                }
            }
        });
    }
}
