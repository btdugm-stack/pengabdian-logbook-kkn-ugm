<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', ['student' => Auth::user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'faculty' => 'nullable|string|max:150',
            'study_program' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:50',
            'emergency_contact' => 'nullable|string|max:100',
        ]);

        Auth::user()->update($data);

        return redirect()->route('profile.edit')->with('flash_success', 'Biodata berhasil diperbarui.');
    }
}
