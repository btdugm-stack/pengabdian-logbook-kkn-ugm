<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use App\Models\Location;
use App\Models\Program;
use App\Models\Theme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Kelola master data logbook (tema, program kerja, jenis kegiatan, lokasi).
 * Melihat: Student::MASTER_DATA_VIEWER_ROLES (Fakultas hanya baca); mengubah:
 * admin saja - keduanya dibatasi di route.
 */
class MasterDataController extends Controller
{
    /** Kunci di URL -> model dan label. */
    private const TYPES = [
        'tema' => ['model' => Theme::class, 'label' => 'Tema', 'column' => 'theme_id'],
        'program' => ['model' => Program::class, 'label' => 'Program Kerja', 'column' => 'program_id'],
        'jenis' => ['model' => ActivityType::class, 'label' => 'Jenis Kegiatan', 'column' => 'activity_type_id'],
        'lokasi' => ['model' => Location::class, 'label' => 'Lokasi', 'column' => 'location_id'],
    ];

    public function index(Request $request): View
    {
        $type = array_key_exists($request->get('jenis'), self::TYPES) ? $request->get('jenis') : 'tema';

        return view('admin.master-data.index', [
            'type' => $type,
            'types' => collect(self::TYPES)->map(fn (array $config) => $config['label']),
            'items' => self::TYPES[$type]['model']::withCount('logbooks')->orderBy('name')->get(),
            'canManage' => $request->user()->isAdmin(),
        ]);
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $item = $this->find($type, $id);

        $data = $request->validateWithBag("item_{$id}", [
            'nama' => ['required', 'string', 'max:150', Rule::unique($item->getTable(), 'name')->ignore($item->id)],
        ], ['nama.unique' => 'Nama ini sudah dipakai data lain. Gunakan Gabungkan untuk menyatukannya.']);

        $item->update(['name' => trim((string) preg_replace('/\s+/u', ' ', strip_tags($data['nama'])))]);

        return $this->back($type, "Nama diubah menjadi \"{$item->name}\".");
    }

    /**
     * Menyatukan data duplikat: semua logbook (dan presensi bantuan, untuk
     * program) dipindah ke data tujuan, lalu data ini dihapus.
     */
    public function merge(Request $request, string $type, int $id): RedirectResponse
    {
        $item = $this->find($type, $id);

        $data = $request->validateWithBag("item_{$id}", [
            'tujuan' => ['required', 'integer', Rule::exists($item->getTable(), 'id'), Rule::notIn([$item->id])],
        ], ['tujuan.*' => 'Pilih data tujuan yang berbeda dari data ini.']);

        $target = $this->find($type, (int) $data['tujuan']);

        DB::transaction(function () use ($item, $target, $type) {
            DB::table('logbooks')->where(self::TYPES[$type]['column'], $item->id)->update([self::TYPES[$type]['column'] => $target->id]);

            if ($type === 'program') {
                DB::table('assist_attendances')->where('program_id', $item->id)->update(['program_id' => $target->id]);
            }

            $item->delete();
        });

        return $this->back($type, "\"{$item->name}\" digabungkan ke \"{$target->name}\".");
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $item = $this->find($type, $id);

        $inUse = $item->logbooks()->exists()
            || ($type === 'program' && DB::table('assist_attendances')->where('program_id', $item->id)->exists());

        if ($inUse) {
            return redirect()->route('admin.master-data.index', ['jenis' => $type])
                ->with('flash_error', "\"{$item->name}\" masih dipakai. Gabungkan ke data lain bila ingin menghilangkannya.");
        }

        $item->delete();

        return $this->back($type, "\"{$item->name}\" dihapus.");
    }

    private function find(string $type, int $id): Model
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        return self::TYPES[$type]['model']::findOrFail($id);
    }

    private function back(string $type, string $message): RedirectResponse
    {
        return redirect()->route('admin.master-data.index', ['jenis' => $type])->with('flash_success', $message);
    }
}
