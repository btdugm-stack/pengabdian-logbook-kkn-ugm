<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\RegistrationRequest;
use App\Support\ParticipantImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Menu Administrasi > Permintaan Pendaftaran: memutuskan pendaftaran mandiri.
 * Akses dibatasi di route (Student::ACCOUNT_MANAGER_ROLES).
 */
class RegistrationRequestController extends Controller
{
    public function __construct(private ParticipantImporter $importer) {}

    public function index(): View
    {
        return view('admin.registrations.index', [
            'pending' => RegistrationRequest::awaitingReview()->oldest()->get(),
            'processed' => RegistrationRequest::where('status', '!=', RegistrationRequest::STATUS_PENDING)
                ->with('reviewer')
                ->latest('reviewed_at')
                ->limit(20)
                ->get(),
            'regionPaths' => Region::paths(),
        ]);
    }

    /**
     * Peran dan wilayah yang berlaku adalah yang dikirim admin di sini, bukan
     * yang diminta pendaftar - permintaan hanya menjadi nilai awal form.
     */
    public function approve(Request $request, RegistrationRequest $registrationRequest): RedirectResponse
    {
        abort_unless($registrationRequest->isPending(), 409, 'Permintaan ini sudah diproses.');

        $row = $this->importer->normalize([
            'email' => $registrationRequest->email,
            'nama' => $registrationRequest->name,
            'peran' => $request->input('peran'),
            'wilayah' => $request->input('wilayah'),
            'fakultas' => $registrationRequest->faculty,
            'prodi' => $registrationRequest->study_program,
            'periode' => $registrationRequest->kkn_period,
            'tema' => $registrationRequest->kkn_theme,
        ]);

        $validator = $this->importer->validator($row);
        $validator->addRules(['peran' => [Rule::in($request->user()->assignableRoles())]]);
        $validator->validateWithBag('request_'.$registrationRequest->id);

        $student = DB::transaction(function () use ($row, $registrationRequest, $request) {
            $student = $this->importer->save($row);

            if (! $student->google_id && $registrationRequest->google_id) {
                $student->update(['google_id' => $registrationRequest->google_id]);
            }

            $registrationRequest->update([
                'status' => RegistrationRequest::STATUS_APPROVED,
                'rejection_reason' => null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            return $student;
        });

        return redirect()->route('admin.registrations.index')
            ->with('flash_success', "Pendaftaran {$student->name} disetujui sebagai {$student->roleLabel()}.");
    }

    public function reject(Request $request, RegistrationRequest $registrationRequest): RedirectResponse
    {
        abort_unless($registrationRequest->isPending(), 409, 'Permintaan ini sudah diproses.');

        $data = $request->validateWithBag('request_'.$registrationRequest->id, [
            'alasan' => ['nullable', 'string', 'max:500'],
        ]);

        $registrationRequest->update([
            'status' => RegistrationRequest::STATUS_REJECTED,
            'rejection_reason' => $data['alasan'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.registrations.index')
            ->with('flash_success', "Pendaftaran {$registrationRequest->name} ditolak.");
    }
}
