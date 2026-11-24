export function tanggalHariIni(): string {
  const sekarang = new Date()
  const bulan = String(sekarang.getMonth() + 1).padStart(2, '0')
  const tanggal = String(sekarang.getDate()).padStart(2, '0')

  return `${sekarang.getFullYear()}-${bulan}-${tanggal}`
}
