<?php

namespace App\Http\Controllers;

use App\Models\UserDocument;
use App\Models\ReferenceDocument;
use App\Models\PlagiarismCheck;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\GoogleDriveService;


class DocumentController extends Controller
{
    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }
    public function upload(Request $request)
    {
        $request->validate([

            'pdf' => 'required|file|mimes:pdf|max:20480',
        ]);
    
        // Simpan file PDF
        $pdfPath = $request->file('pdf')->store('documents');
    
        // Ekstrak teks dari PDF (gunakan library seperti Smalot/PdfParser)
        $content = $this->extractTextFromPdf($pdfPath);
        $fileName = $request->file('pdf')->getClientOriginalName();
        $driveFileId = $this->googleDriveService->uploadFile($content, $fileName);
        // Simpan dokumen user
        $userDocument = UserDocument::create([
            'file_name' => $fileName,
            'content' => $content,
            'pdf_path' => $pdfPath,
            'drive_file_id'=>$driveFileId,
            'user_id' => Auth::id(),
        ]);
    
        // Arahkan ke pemeriksaan plagiarisme
        return redirect()->route('plagiarism.check', ['id' => $userDocument->id]);
    }
    

    public function uploadReference(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'pdf' => 'required|file|mimes:pdf|max:20480',
        ]);

        // Simpan file PDF
        $pdfPath = $request->file('pdf')->store('reference_documents');

        // Ekstrak teks dari PDF
        $content = $this->extractTextFromPdf($pdfPath);

        // Simpan dokumen referensi
        ReferenceDocument::create([
            'title' => $request->title,
            'content' => $content,
            'pdf_path' => $pdfPath,
        ]);

        return redirect()->back()->with('success', 'Dokumen referensi berhasil diunggah.');
    }

    public function checkPlagiarism($userDocumentId)
    {
        // Dapatkan dokumen yang diunggah oleh user
        $userDocument = UserDocument::findOrFail($userDocumentId);

        // Ambil semua dokumen referensi di database
        $referenceDocuments = ReferenceDocument::all();

        // Array untuk menyimpan hasil pemeriksaan plagiarisme
        $plagiarismResults = [];

        // Loop melalui setiap dokumen referensi untuk perbandingan
        foreach ($referenceDocuments as $referenceDocument) {
            // Hitung persentase kemiripan teks menggunakan metode TF-IDF + Cosine Similarity
            $similarity = $this->calculateTfidfSimilarity($userDocument->content, $referenceDocument->content);
            $matchedText = $this->calculateLcs($userDocument->content, $referenceDocument->content);

            // Simpan hasil perbandingan ke array
            $plagiarismResults[] = [
                'reference_document_id' => $referenceDocument->id,
                'similarity_percentage' => number_format($similarity, 2),
                'matched_text' => $matchedText,
            ];

            // Simpan hasil pemeriksaan ke tabel plagiarism_checks
            PlagiarismCheck::create([
                'user_document_id' => $userDocument->id,
                'reference_document_id' => $referenceDocument->id,
                'similarity_percentage' => $similarity,
            ]);
        }




        // Kembalikan hasil ke view
        return view('plagiarism_results', [
            'document' => $userDocument,
            'results' => $plagiarismResults,

        ]);
    }

    // Fungsi untuk menghitung kemiripan menggunakan TF-IDF dan Cosine Similarity
    private function calculateTfidfSimilarity($text1, $text2)
    {
        // Tokenisasi dan hapus kata umum (stop words)

        $stopWords = $this->getStopWords();
        // Tambahkan lebih banyak kata jika perlu
        $tokens1 = array_diff(explode(' ', strtolower($text1)), $stopWords);
        $tokens2 = array_diff(explode(' ', strtolower($text2)), $stopWords);

        // Gabungkan kedua set token untuk menghitung TF-IDF
        $allTokens = array_unique(array_merge($tokens1, $tokens2));

        // Hitung TF (Term Frequency) untuk kedua teks
        $tf1 = $this->calculateTf($tokens1, $allTokens);
        $tf2 = $this->calculateTf($tokens2, $allTokens);

        // Hitung IDF (Inverse Document Frequency)
        $idf = $this->calculateIdf($tokens1, $tokens2);

        // Hitung TF-IDF untuk kedua teks
        $tfidf1 = $this->calculateTfidf($tf1, $idf);
        $tfidf2 = $this->calculateTfidf($tf2, $idf);

        // Hitung cosine similarity
        return $this->calculateCosineSimilarity($tfidf1, $tfidf2) * 100; // Kembalikan dalam persen
    }
    private function calculateLcs($text1, $text2)
    {


        $words1 = explode(' ', strtolower($text1));
        $words2 = explode(' ', strtolower($text2));
        $length1 = count($words1);
        $length2 = count($words2);

        // Membuat tabel DP
        $dp = array_fill(0, $length1 + 1, array_fill(0, $length2 + 1, 0));

        // Mengisi tabel DP
        for ($i = 1; $i <= $length1; $i++) {
            for ($j = 1; $j <= $length2; $j++) {
                if ($words1[$i - 1] === $words2[$j - 1]) {
                    $dp[$i][$j] = $dp[$i - 1][$j - 1] + 1;
                } else {
                    $dp[$i][$j] = max($dp[$i - 1][$j], $dp[$i][$j - 1]);
                }
            }
        }

        // Mengambil teks yang cocok dari tabel DP
        $lcsLength = $dp[$length1][$length2];
        $lcs = [];
        for ($i = $length1, $j = $length2; $i > 0 && $j > 0;) {
            if ($words1[$i - 1] === $words2[$j - 1]) {
                $lcs[] = $words1[$i - 1];
                $i--;
                $j--;
            } else if ($dp[$i - 1][$j] > $dp[$i][$j - 1]) {
                $i--;
            } else {
                $j--;
            }
        }

        return implode(' ', array_reverse($lcs)); // Mengembalikan teks yang cocok
    }

    // Fungsi untuk menghitung TF (Term Frequency)
    private function calculateTf($tokens, $allTokens)
    {

        $tf = [];
        $tokenCount = count($tokens);

        foreach ($allTokens as $token) {
            $tf[$token] = in_array($token, $tokens) ? count(array_keys($tokens, $token)) / $tokenCount : 0;
        }

        return $tf;
    }

    // Fungsi untuk menghitung IDF (Inverse Document Frequency)
    private function calculateIdf($tokens1, $tokens2)
    {
        $idf = [];
        $documents = [$tokens1, $tokens2];
        $totalDocuments = count($documents);

        foreach (array_unique(array_merge($tokens1, $tokens2)) as $token) {
            $containingDocs = 0;
            foreach ($documents as $doc) {
                if (in_array($token, $doc)) {
                    $containingDocs++;
                }
            }
            $idf[$token] = log($totalDocuments / ($containingDocs + 1)) + 1; // Menghindari pembagian dengan nol
        }

        return $idf;
    }

    // Fungsi untuk menghitung TF-IDF
    private function calculateTfidf($tf, $idf)
    {
        $tfidf = [];

        foreach ($tf as $token => $tfValue) {
            $tfidf[$token] = $tfValue * $idf[$token];
        }

        return $tfidf;
    }

    // Fungsi untuk menghitung cosine similarity
    private function calculateCosineSimilarity($vector1, $vector2)
    {
        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        foreach ($vector1 as $token => $value) {
            $dotProduct += $value * ($vector2[$token] ?? 0);
            $magnitude1 += $value * $value;
        }

        foreach ($vector2 as $value) {
            $magnitude2 += $value * $value;
        }

        // Menghindari pembagian dengan nol
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($magnitude1) * sqrt($magnitude2));
    }

    // Fungsi untuk ekstrak teks dari PDF
    private function extractTextFromPdf($pdfPath)
    {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile(storage_path(path: 'app/private/' . $pdfPath));
        $text = $pdf->getText();

        // Inisialisasi stemmer dari Sastrawi
        $stemmerFactory = new \Sastrawi\Stemmer\StemmerFactory();
        $stemmer = $stemmerFactory->createStemmer();

        // Lakukan stemming pada teks yang diekstrak
        $stemmedText = $stemmer->stem($text);

        return $stemmedText;
    }

    private function getStopWords()
    {
        $url = 'http://localhost:5000/api/stopwords'; // URL API Flask
        $response = file_get_contents($url); // Ambil data dari API
    
        // Cek jika permintaan berhasil
        if ($response === FALSE) {
            return []; // Kembalikan array kosong jika gagal
        }
    
        $stopWords = json_decode($response, true); // Decode JSON menjadi array
        return $stopWords; // Kembalikan daftar stopword
    }
    
}