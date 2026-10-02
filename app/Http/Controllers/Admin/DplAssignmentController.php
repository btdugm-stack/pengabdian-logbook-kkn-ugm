<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Menentukan mahasiswa bimbingan DPL: oleh admin untuk DPL mana pun (menu
 * Administrasi > Penugasan DPL), atau oleh DPL untuk dirinya sendiri (menu
 * Mahasiswa Bimbingan).
 * Penugasan ini yang menjadi cakupan DPL untuk reviu logbook, overview,
 * pencarian logbook, dan notifikasi eskalasi (Student::supervisedStudents()).
 * Akses dibatasi di route.
 */
class DplAssignmentController extends Controller
{
    public function index(): View
    {
        return view('admin.dpl-assignments.index', [
            'dpls' => Student::role(Student::ASSIGNED_SCOPE_ROLES)->withCount('advisees')->orderBy('name')->get(),
            'unassigned' => Student::participants()->whereDoesntHave('advisors')->count(),
            'participants' => Student::participants()->count(),
        ]);
    }

    public function edit(Student $dpl): View
    {
        abort_unless($dpl->hasAnyRole(Student::ASSIGNED_SCOPE_ROLES), 404);

        return $this->form($dpl, own: false);
    }

    public function update(Request $request, Student $dpl): RedirectResponse
    {
        abort_unless($dpl->hasAnyRole(Student::ASSIGNED_SCOPE_ROLES), 404);

        return redirect()->route('admin.dpl-assignments.index')->with('flash_success', $this->sync($request, $dpl));
    }

    /**
     * DPL memilih sendiri mahasiswa bimbingannya (menu Mahasiswa Bimbingan).
     * Selalu atas akun yang sedang login - tidak ada id DPL di URL, jadi tidak
     * bisa mengatur bimbingan DPL lain. Dibatasi di route (ASSIGNED_SCOPE_ROLES).
     */
    public function editOwn(Request $request): View
    {
        return $this->form($request->user(), own: true);
    }

    public function updateOwn(Request $request): RedirectResponse
    {
        return redirect()->route('advisees.edit')->with('flash_success', $this->sync($request, $request->user()));
    }

    private function form(Student $dpl, bool $own): View
    {
        $participants = Student::participants()
            ->with(['region.parent.parent.parent', 'roles', 'advisors:id,name'])
            ->orderBy('name')
            ->get();

        return view('admin.dpl-assignments.edit', [
            'dpl' => $dpl,
            'own' => $own,
            'assignedIds' => $dpl->advisees()->pluck('students.id')->all(),
            // Kelompok = wilayah penempatan (umumnya sub-unit), supaya satu kelompok bisa ditugaskan sekaligus.
            'groups' => $participants
                ->groupBy(fn (Student $student) => $student->region?->fullPath() ?? 'Wilayah belum diisi')
                ->sortKeys(),
        ]);
    }

    /** Simpan daftar bimbingan $dpl dari form; mengembalikan pesan hasilnya. */
    private function sync(Request $request, Student $dpl): string
    {
        $data = $request->validate([
            'mahasiswa' => ['array'],
            // Hanya peserta KKN yang bisa dibimbing - bukan akun DPL, admin, atau id sembarang.
            'mahasiswa.*' => ['integer', Rule::in(Student::participants()->pluck('id')->all())],
        ]);

        $changes = $dpl->advisees()->sync($data['mahasiswa'] ?? []);

        return sprintf(
            'Bimbingan %s diperbarui: %d ditambahkan, %d dilepas. Sekarang membimbing %d mahasiswa.',
            $dpl->name, count($changes['attached']), count($changes['detached']), $dpl->advisees()->count(),
        );
    }
}
