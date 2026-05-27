<?php
// classes/DosenRepository.php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class DosenRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    // ── READ ─────────────────────────────────────────────────

    // Ambil semua dosen aktif (belum dihapus) + jumlah MK
    public function getAll(string $cari = '', int $limit = 10, int $offset = 0): array
    {
        $sql = 'SELECT d.*,
                (SELECT COUNT(*) FROM dosen_matkul dm WHERE dm.dosen_id = d.id) AS jml_matkul
                FROM dosen d
                WHERE d.deleted_at IS NULL';

        $params = [];
        if ($cari !== '') {
            $sql .= ' AND (d.nama LIKE :cari OR d.nidn LIKE :cari2 OR d.email LIKE :cari3)';
            $params[':cari']  = "%{$cari}%";
            $params[':cari2'] = "%{$cari}%";
            $params[':cari3'] = "%{$cari}%";
        }

        $sql .= ' ORDER BY d.nama ASC LIMIT :limit OFFSET :offset';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // Hitung total dosen aktif (untuk paginasi)
    public function countAll(string $cari = ''): int
    {
        $sql    = 'SELECT COUNT(*) FROM dosen WHERE deleted_at IS NULL';
        $params = [];

        if ($cari !== '') {
            $sql .= ' AND (nama LIKE :cari OR nidn LIKE :cari2 OR email LIKE :cari3)';
            $params[':cari']  = "%{$cari}%";
            $params[':cari2'] = "%{$cari}%";
            $params[':cari3'] = "%{$cari}%";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // Ambil satu dosen berdasarkan ID
    public function findById(int $id): array|false
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM dosen WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Ambil semua dosen yang sudah dihapus (untuk trash)
    public function getTrashed(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM dosen WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC'
        );
        return $stmt->fetchAll();
    }

    // ── CREATE ───────────────────────────────────────────────

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO dosen (nidn, nama, email, program_studi, foto, status)
             VALUES (:nidn, :nama, :email, :program_studi, :foto, :status)'
        );
        $stmt->execute([
            ':nidn'          => $data['nidn'],
            ':nama'          => $data['nama'],
            ':email'         => $data['email'],
            ':program_studi' => $data['program_studi'],
            ':foto'          => $data['foto'] ?? null,
            ':status'        => $data['status'] ?? 'aktif',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    // ── UPDATE ───────────────────────────────────────────────

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE dosen SET nidn=:nidn, nama=:nama, email=:email,
             program_studi=:program_studi, foto=:foto, status=:status
             WHERE id=:id AND deleted_at IS NULL'
        );
        return $stmt->execute([
            ':nidn'          => $data['nidn'],
            ':nama'          => $data['nama'],
            ':email'         => $data['email'],
            ':program_studi' => $data['program_studi'],
            ':foto'          => $data['foto'],
            ':status'        => $data['status'],
            ':id'            => $id,
        ]);
    }

    // ── SOFT DELETE ──────────────────────────────────────────

    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE dosen SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL'
        );
        return $stmt->execute([':id' => $id]);
    }

    // ── RESTORE ──────────────────────────────────────────────

    public function restore(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE dosen SET deleted_at = NULL WHERE id = :id AND deleted_at IS NOT NULL'
        );
        return $stmt->execute([':id' => $id]);
    }

    // ── CEK DUPLIKAT ─────────────────────────────────────────

    public function isNidnExist(string $nidn, int $kecualiId = 0): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM dosen WHERE nidn = :nidn AND id != :id'
        );
        $stmt->execute([':nidn' => $nidn, ':id' => $kecualiId]);
        return (bool)$stmt->fetch();
    }

    public function isEmailExist(string $email, int $kecualiId = 0): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM dosen WHERE email = :email AND id != :id'
        );
        $stmt->execute([':email' => $email, ':id' => $kecualiId]);
        return (bool)$stmt->fetch();
    }

    // ── RELASI MANY-TO-MANY (dosen ↔ mata kuliah) ────────────

    // Simpan relasi dalam transaksi
    public function syncMatkul(int $dosenId, array $matkulIds, string $tahunAjar = '2025/2026'): void
    {
        $this->pdo->beginTransaction();
        try {
            // Hapus relasi lama
            $del = $this->pdo->prepare('DELETE FROM dosen_matkul WHERE dosen_id = :id');
            $del->execute([':id' => $dosenId]);

            // Insert relasi baru
            $ins = $this->pdo->prepare(
                'INSERT INTO dosen_matkul (dosen_id, matkul_id, tahun_ajar)
                 VALUES (:dosen_id, :matkul_id, :tahun_ajar)'
            );
            foreach ($matkulIds as $mkId) {
                $ins->execute([
                    ':dosen_id'  => $dosenId,
                    ':matkul_id' => (int)$mkId,
                    ':tahun_ajar'=> $tahunAjar,
                ]);
            }

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // Ambil mata kuliah yang diampu dosen tertentu
    public function getMatkulByDosen(int $dosenId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT mk.*, dm.tahun_ajar
             FROM mata_kuliah mk
             JOIN dosen_matkul dm ON mk.id = dm.matkul_id
             WHERE dm.dosen_id = :id'
        );
        $stmt->execute([':id' => $dosenId]);
        return $stmt->fetchAll();
    }
}
