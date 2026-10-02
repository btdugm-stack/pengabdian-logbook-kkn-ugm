<?php

namespace App\Http\Controllers;

use App\Support\ProfileOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', [
            'student' => Auth::user(),
            'faculties' => ProfileOptions::faculties(),
            'studyPrograms' => ProfileOptions::studyPrograms(),
            'periods' => ProfileOptions::periods(),
            'themes' => ProfileOptions::themes(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date|before:today',
            'faculty' => 'nullable|string|max:150',
            'study_program' => 'nullable|string|max:150',
            'kkn_period' => 'nullable|string|max:100',
            'kkn_theme' => 'nullable|string|max:150',
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-\s()]+$/'],
            'emergency_contact' => 'nullable|string|max:100',
        ]);

        foreach (['faculty' => ProfileOptions::faculties(), 'study_program' => ProfileOptions::studyPrograms(), 'kkn_period' => ProfileOptions::periods(), 'kkn_theme' => ProfileOptions::themes()] as $field => $options) {
            if (array_key_exists($field, $data)) {
                $data[$field] = ProfileOptions::canonical($data[$field], $options) ?: null;
            }
        }

        Auth::user()->update($data);

        return redirect()->route('profile.edit')->with('flash_success', 'Biodata berhasil diperbarui.');
    }
}
