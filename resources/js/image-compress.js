/**
 * Kompresi gambar client-side sebelum upload.
 *
 * Gambar raster (JPG/PNG/WebP) yang ukurannya melebihi ambang (default 2 MB)
 * dikompres via canvas: diturunkan ke dimensi maks (default 1600 px) lalu
 * di-encode sebagai JPEG dengan kualitas yang diturunkan bertahap sampai
 * hasilnya di bawah ambang. Hasilnya menggantikan file di <input type="file">
 * (via DataTransfer) sehingga form tetap mengirim file terkompres.
 *
 * API global:
 *   window.mayCompressImage(file)            -> boolean
 *   window.processImageFile(input, file)     -> Promise<File>
 */

const RASTER_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const DEFAULT_MAX_BYTES = 2 * 1024 * 1024; // 2 MB
const DEFAULT_MAX_DIMENSION = 1600;
const DEFAULT_QUALITY = 0.85;
const MIN_QUALITY = 0.4;

function mayCompressImage(file, maxBytes = DEFAULT_MAX_BYTES) {
    return !!file && RASTER_TYPES.includes(file.type) && file.size > maxBytes;
}

function readAsImage(file) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            URL.revokeObjectURL(url);
            resolve(img);
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('Gambar tidak dapat dibaca.'));
        };
        img.src = url;
    });
}

function canvasToJpegBlob(canvas, quality) {
    return new Promise((resolve, reject) => {
        canvas.toBlob(
            (blob) => (blob ? resolve(blob) : reject(new Error('Encoding JPEG gagal.'))),
            'image/jpeg',
            quality
        );
    });
}

function compressImage(file, {
    maxBytes = DEFAULT_MAX_BYTES,
    maxDimension = DEFAULT_MAX_DIMENSION,
    quality = DEFAULT_QUALITY,
} = {}) {
    return readAsImage(file).then((img) => {
        const scale = Math.min(1, maxDimension / img.width, maxDimension / img.height);
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(img.width * scale));
        canvas.height = Math.max(1, Math.round(img.height * scale));

        const ctx = canvas.getContext('2d');
        // latar putih agar area transparan PNG/WebP tidak menjadi hitam di JPEG
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

        let q = quality;
        const encode = () => canvasToJpegBlob(canvas, q).then((blob) => {
            if (blob.size <= maxBytes || q <= MIN_QUALITY) {
                const base = file.name.replace(/\.[^.]+$/, '') || 'gambar';
                return new File([blob], base + '-kompres.jpg', { type: 'image/jpeg' });
            }
            q -= 0.1;
            return encode();
        });

        return encode();
    });
}

/**
 * Kompres bila perlu; ganti isi input.files dengan hasil kompresi.
 * Mengembalikan File yang akan dikirim (file asli bila tidak perlu kompresi).
 */
async function processImageFile(input, file) {
    if (!mayCompressImage(file)) {
        return file;
    }

    const compressed = await compressImage(file);

    const dt = new DataTransfer();
    dt.items.add(compressed);
    input.files = dt.files;

    return compressed;
}

if (typeof window !== 'undefined') {
    window.mayCompressImage = mayCompressImage;
    window.processImageFile = processImageFile;
}

export { mayCompressImage, compressImage, processImageFile };
