@once
@push('scripts')
<script>
    // 공용 CRUD 모달 상태: 신규/수정 공용 (폼은 form.* 에 바인딩, action/_method 자동)
    function crudModal(initOpen = false, initForm = {}) {
        return {
            open: initOpen,
            mode: 'create',
            action: '',
            method: 'POST',
            form: Object.assign({}, initForm),
            imgPreview: null,   // 새로 선택한 파일의 미리보기(data URL)
            removeImage: false, // 기존 이미지 삭제 요청 여부
            openCreate(action, defaults = {}) {
                this.mode = 'create'; this.method = 'POST'; this.action = action;
                this.form = Object.assign({}, defaults);
                this.resetImage();
                this.open = true;
            },
            openEdit(action, data = {}) {
                this.mode = 'edit'; this.method = 'PUT'; this.action = action;
                this.form = Object.assign({}, data);
                this.resetImage();
                this.open = true;
            },
            resetImage() {
                this.imgPreview = null; this.removeImage = false;
                if (this.$refs.imageInput) this.$refs.imageInput.value = '';
            },
            pickImage(e) {
                const f = e.target.files[0];
                if (!f) return;
                this.removeImage = false;
                const r = new FileReader();
                r.onload = ev => { this.imgPreview = ev.target.result; };
                r.readAsDataURL(f);
            },
            dropImage() {
                this.imgPreview = null; this.removeImage = true;
                this.form.image = null;
                if (this.$refs.imageInput) this.$refs.imageInput.value = '';
            },
        }
    }
</script>
@endpush
@endonce
