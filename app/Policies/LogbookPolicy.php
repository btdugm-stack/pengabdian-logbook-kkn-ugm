<?php

namespace App\Policies;

use App\Models\Logbook;
use App\Models\Student;
use Illuminate\Auth\Access\Response;

class LogbookPolicy
{
    /**
     * Admin boleh menginput logbook atas nama peserta (koreksi data), bukan
     * atas nama akun yang memang tidak punya logbook.
     */
    public function createFor(Student $actor, Student $owner): bool
    {
        return $actor->isAdmin() && $owner->isParticipant();
    }

    /**
     * Pemilik atau admin boleh mengubah logbook, dan hanya sebelum keputusan
     * akhir: draft, yang menunggu reviu, dan yang diminta revisi masih bisa
     * dikoreksi. Begitu disetujui atau ditolak, isinya dikunci supaya yang
     * dinilai tidak berubah diam-diam. Logbook milik orang lain dijawab 404
     * supaya keberadaannya tidak terkonfirmasi.
     */
    public function update(Student $student, Logbook $logbook): Response
    {
        if ((int) $logbook->student_id !== (int) $student->id && ! $student->isAdmin()) {
            return Response::denyAsNotFound();
        }

        return $logbook->isFinal()
            ? Response::deny('Logbook yang sudah disetujui atau ditolak tidak bisa diubah atau dihapus.')
            : Response::allow();
    }

    /**
     * Menghapus hanya boleh sebelum ada keputusan reviu. Logbook yang diminta
     * revisi harus diperbaiki, bukan dihapus: menghapusnya ikut membuang
     * lembar revisi dari pembimbing.
     */
    public function delete(Student $student, Logbook $logbook): Response
    {
        // Admin mengoreksi, tidak menghapus: logbook hanya bisa dihapus pemiliknya.
        if ((int) $logbook->student_id !== (int) $student->id) {
            return Response::denyAsNotFound();
        }

        $response = $this->update($student, $logbook);

        if ($response->allowed() && $logbook->needsRevision()) {
            return Response::deny('Logbook yang diminta revisi tidak bisa dihapus. Perbaiki lalu kirim ulang.');
        }

        return $response;
    }

    /**
     * Mengambil keputusan reviu (setujui / minta revisi / tolak): peran
     * reviewer, logbook sedang menunggu reviu, dan mahasiswanya berada dalam
     * cakupan wilayah akun tersebut.
     */
    public function review(Student $student, Logbook $logbook): bool
    {
        return $logbook->isSubmitted() && $this->viewForReview($student, $logbook);
    }

    /** Membuka halaman reviu beserta riwayatnya, juga setelah keputusan diambil. Draft tidak pernah terlihat. */
    public function viewForReview(Student $student, Logbook $logbook): bool
    {
        return $student->hasAnyRole(Student::REVIEWER_ROLES)
            && ! $logbook->isDraft()
            && $student->canSupervise($logbook->student);
    }
}
