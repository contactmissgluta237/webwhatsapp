// whatsapp-bridge/src/services/FileSystemService.js
const fs = require("fs");
const path = require("path");

class FileSystemService {
    static async cleanupSessionFiles(sessionId) {
        try {
            const sessionPath = path.join(
                process.cwd(),
                ".wwebjs_auth",
                `session-${sessionId}`,
            );

            if (fs.existsSync(sessionPath)) {
                await this.deleteFolderRecursive(sessionPath);
                console.log(`[FileSystem] Session files cleaned: ${sessionId}`);
            }
        } catch (error) {
            console.error(
                `[FileSystem] Cleanup failed for ${sessionId}:`,
                error.message,
            );
        }
    }

    static async deleteFolderRecursive(folderPath) {
        if (fs.existsSync(folderPath)) {
            fs.readdirSync(folderPath).forEach((file) => {
                const curPath = path.join(folderPath, file);
                if (fs.lstatSync(curPath).isDirectory()) {
                    this.deleteFolderRecursive(curPath);
                } else {
                    fs.unlinkSync(curPath);
                }
            });
            fs.rmdirSync(folderPath);
        }
    }
}

module.exports = FileSystemService;
