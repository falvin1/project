<x-app-layout>

    <div class="container">
        <h1>Upload Dokumen</h1>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form action="{{ route('document.upload') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label for="pdf" class="form-label">Unggah PDF</label>
                <input type="file" name="pdf" class="form-control" accept="application/pdf" required>
            </div>
            <button type="submit" class="btn btn-primary">Unggah</button>
        </form>
    </div>

</x-app-layout>
