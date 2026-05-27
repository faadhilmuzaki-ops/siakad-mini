<?php
// classes/Validator.php
declare(strict_types=1);

class Validator
{
    private array $errors = [];

    // Reset errors
    public function reset(): void
    {
        $this->errors = [];
    }

    // Cek apakah ada error
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    // Ambil semua error
    public function getErrors(): array
    {
        return $this->errors;
    }

    // Tambah error manual
    public function addError(string $pesan): void
    {
        $this->errors[] = $pesan;
    }

    // Wajib diisi
    public function required(string $nilai, string $label): void
    {
        if (trim($nilai) === '') {
            $this->errors[] = "{$label} wajib diisi.";
        }
    }

    // Panjang maksimal
    public function maxLength(string $nilai, int $maks, string $label): void
    {
        if (mb_strlen(trim($nilai)) > $maks) {
            $this->errors[] = "{$label} maksimal {$maks} karakter.";
        }
    }

    // Format email
    public function email(string $nilai, string $label = 'Email'): void
    {
        if (!filter_var(trim($nilai), FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = "Format {$label} tidak valid.";
        }
    }

    // Hanya angka dengan panjang tertentu
    public function digits(string $nilai, int $panjang, string $label): void
    {
        if (!preg_match('/^\d{' . $panjang . '}$/', trim($nilai))) {
            $this->errors[] = "{$label} harus {$panjang} digit angka.";
        }
    }

    // Harus salah satu dari pilihan yang valid
    public function inList(string $nilai, array $pilihan, string $label): void
    {
        if (!in_array($nilai, $pilihan, true)) {
            $this->errors[] = "{$label} tidak valid.";
        }
    }
}
