import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('mitraPhoto', () => ({
    preview: null,
    stream: null,
    camera: false,
    busy: false,
    error: '',
    cancelled: false,

    init() {
        const form = this.$el.closest('form');
        this.submitListener = () => this.stop();
        form?.addEventListener('submit', this.submitListener);
        this.form = form;
    },

    selected() {
        this.error = '';

        if (this.preview) {
            URL.revokeObjectURL(this.preview);
        }

        const file = this.$refs.photo.files?.[0];
        this.preview = file ? URL.createObjectURL(file) : null;

        if (file && file.size > 100 * 1024 * 1024) {
            this.error = 'Foto melebihi 100 MB. Pilih foto yang lebih kecil.';
        }
    },

    cameraErrorMessage(error) {
        const messages = {
            NotAllowedError: 'Izin kamera ditolak. Aktifkan izin kamera untuk situs ini, lalu coba kembali.',
            SecurityError: 'Browser memblokir akses kamera. Gunakan localhost atau HTTPS dan periksa izin situs.',
            NotFoundError: 'Kamera tidak ditemukan pada perangkat ini. Gunakan upload foto.',
            NotReadableError: 'Kamera sedang digunakan aplikasi lain atau tidak dapat dibuka. Tutup aplikasi kamera lain, lalu coba kembali.',
            OverconstrainedError: 'Kamera tidak mendukung pengaturan yang diminta. Coba kembali atau gunakan upload foto.',
            AbortError: 'Akses kamera terputus sebelum siap. Silakan coba kembali.',
        };

        return messages[error?.name] ?? 'Kamera tidak dapat dibuka. Periksa izin kamera atau gunakan upload foto.';
    },

    async openCamera() {
        this.error = '';

        if (this.busy || this.camera) {
            return;
        }

        if (!window.isSecureContext) {
            this.error = 'Browser hanya mengizinkan kamera melalui HTTPS atau localhost. Buka aplikasi melalui http://127.0.0.1 atau HTTPS.';
            return;
        }

        if (!navigator.mediaDevices?.getUserMedia) {
            this.error = 'Fitur kamera tidak tersedia pada browser ini. Gunakan browser terbaru atau upload foto.';
            return;
        }

        this.busy = true;
        this.cancelled = false;

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'user' },
                    width: { ideal: 900 },
                    height: { ideal: 1200 },
                },
                audio: false,
            });

            if (this.cancelled) {
                stream.getTracks().forEach((track) => track.stop());
                return;
            }

            this.stream = stream;
            const videoTrack = stream.getVideoTracks()[0];
            videoTrack?.addEventListener('ended', () => {
                if (this.stream === stream) {
                    this.stop();
                    this.error = 'Koneksi kamera terputus. Buka kamera kembali atau upload foto.';
                }
            }, { once: true });

            this.camera = true;
            await this.$nextTick();

            const video = this.$refs.video;
            if (!video) {
                throw new Error('Elemen pratinjau kamera tidak ditemukan.');
            }

            video.srcObject = stream;
            await video.play();
        } catch (error) {
            this.stop();
            this.error = this.cameraErrorMessage(error);
        } finally {
            this.busy = false;
        }
    },

    async capture() {
        const video = this.$refs.video;

        if (!video?.videoWidth || this.busy) {
            this.error = 'Tunggu sampai pratinjau kamera tampil sebelum mengambil foto.';
            return;
        }

        this.busy = true;
        this.error = '';

        try {
            const canvas = document.createElement('canvas');
            const scale = Math.min(1, 1200 / Math.max(video.videoWidth, video.videoHeight));
            canvas.width = Math.round(video.videoWidth * scale);
            canvas.height = Math.round(video.videoHeight * scale);

            const context = canvas.getContext('2d');
            if (!context) {
                throw new Error('Canvas tidak tersedia.');
            }

            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
            if (!blob || this.cancelled) {
                throw new Error('Foto gagal dibuat.');
            }

            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'foto-kamera.jpg', { type: 'image/jpeg' }));
            this.$refs.photo.files = transfer.files;

            this.selected();
            this.stop();
        } catch (error) {
            this.error = 'Foto belum berhasil disimpan. Coba ambil ulang atau gunakan upload foto.';
        } finally {
            this.busy = false;
        }
    },

    stop() {
        this.cancelled = true;

        const stream = this.stream;
        this.stream = null;
        stream?.getTracks().forEach((track) => track.stop());

        this.camera = false;

        if (this.$refs.video) {
            this.$refs.video.srcObject = null;
        }
    },

    destroy() {
        this.stop();

        if (this.preview) {
            URL.revokeObjectURL(this.preview);
        }

        this.form?.removeEventListener('submit', this.submitListener);
    },
}));

Alpine.start();
