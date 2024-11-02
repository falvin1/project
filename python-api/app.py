from flask import Flask, jsonify
import nltk
from nltk.corpus import stopwords

# Download stopwords jika belum di-download
nltk.download('stopwords')

app = Flask(__name__)

@app.route('/api/stopwords', methods=['GET'])
def get_stopwords():
    # Ambil daftar stopwords Bahasa Indonesia
    stop_words = set(stopwords.words('indonesian'))
    
    # Konversi set ke list dan urutkan
    stop_words_list = sorted(list(stop_words))
    
    return jsonify(stop_words_list)

if __name__ == '__main__':
    app.run(port=5000)  # Jalankan di port 5000