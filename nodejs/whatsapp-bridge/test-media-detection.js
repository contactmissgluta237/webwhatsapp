/**
 * Test de détection de tous types de médias
 */

// Copie de la fonction de détection pour test
const isMediaUrl = (text) => {
    // Images
    const imageUrlRegex = /^https?:\/\/.*\.(jpg|jpeg|png|gif|webp|bmp|tiff|svg)($|\?)/i;
    const unsplashRegex = /^https?:\/\/images\.unsplash\.com\//i;
    
    // Videos
    const videoUrlRegex = /^https?:\/\/.*\.(mp4|avi|mov|wmv|flv|webm|mkv|m4v)($|\?)/i;
    
    // Documents
    const documentUrlRegex = /^https?:\/\/.*\.(pdf|doc|docx|xls|xlsx|ppt|pptx|txt|rtf)($|\?)/i;
    
    // Audio
    const audioUrlRegex = /^https?:\/\/.*\.(mp3|wav|ogg|aac|flac|m4a|wma)($|\?)/i;
    
    // Archives
    const archiveUrlRegex = /^https?:\/\/.*\.(zip|rar|7z|tar|gz)($|\?)/i;
    
    // Known media hosting domains
    const mediaHostingRegex = /^https?:\/\/(images|video|media|files|docs|drive)\./i;
    
    return imageUrlRegex.test(text) || 
           unsplashRegex.test(text) ||
           videoUrlRegex.test(text) ||
           documentUrlRegex.test(text) ||
           audioUrlRegex.test(text) ||
           archiveUrlRegex.test(text) ||
           mediaHostingRegex.test(text);
};

const testUrls = [
    // Images
    { url: "https://example.com/image.jpg", type: "Image JPG", shouldDetect: true },
    { url: "https://images.unsplash.com/photo-123", type: "Unsplash", shouldDetect: true },
    { url: "https://example.com/photo.png", type: "Image PNG", shouldDetect: true },
    
    // Videos
    { url: "https://example.com/video.mp4", type: "Video MP4", shouldDetect: true },
    { url: "https://example.com/movie.avi", type: "Video AVI", shouldDetect: true },
    
    // Documents
    { url: "https://example.com/document.pdf", type: "PDF", shouldDetect: true },
    { url: "https://example.com/spreadsheet.xlsx", type: "Excel", shouldDetect: true },
    { url: "https://example.com/presentation.pptx", type: "PowerPoint", shouldDetect: true },
    
    // Audio
    { url: "https://example.com/music.mp3", type: "Audio MP3", shouldDetect: true },
    { url: "https://example.com/sound.wav", type: "Audio WAV", shouldDetect: true },
    
    // Archives
    { url: "https://example.com/archive.zip", type: "Archive ZIP", shouldDetect: true },
    { url: "https://example.com/backup.rar", type: "Archive RAR", shouldDetect: true },
    
    // Hosting domains
    { url: "https://files.example.com/something", type: "Files domain", shouldDetect: true },
    { url: "https://media.example.com/content", type: "Media domain", shouldDetect: true },
    
    // Non-media (should NOT detect)
    { url: "https://example.com/page.html", type: "HTML page", shouldDetect: false },
    { url: "Just some text", type: "Plain text", shouldDetect: false },
    { url: "https://example.com/api/data", type: "API endpoint", shouldDetect: false },
];

console.log("🧪 Test de détection de médias\n");
console.log("=".repeat(60));

let correct = 0;
let total = testUrls.length;

testUrls.forEach((test, index) => {
    const detected = isMediaUrl(test.url);
    const result = detected === test.shouldDetect;
    const status = result ? "✅" : "❌";
    const detection = detected ? "MÉDIA" : "TEXTE";
    
    console.log(`${(index + 1).toString().padStart(2)}. ${status} ${detection} | ${test.type.padEnd(15)} | ${test.url.substring(0, 40)}...`);
    
    if (result) correct++;
});

console.log("=".repeat(60));
console.log(`📊 Résultat : ${correct}/${total} tests réussis (${((correct/total)*100).toFixed(1)}%)`);

if (correct === total) {
    console.log("🎉 Tous les tests sont passés ! La détection fonctionne parfaitement.");
} else {
    console.log("⚠️ Certains tests ont échoué, vérifier la logique de détection.");
}