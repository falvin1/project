<x-app-layout>

    <div class="container">
        <h1>Hasil Pemeriksaan Plagiarisme</h1>

        <h3>Dokumen: {{ $document->title }}</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Dokumen Referensi ID</th>
                    <th>Persentase Kemiripan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($results as $result)
                    <tr class="border border-red-300">
                        <td>{{ $result['reference_document_id'] }}</td>
                        <td>{{ $result['similarity_percentage'] }}%</td>
                        <td>{{ $result['matched_text'] }} </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</x-app-layout>