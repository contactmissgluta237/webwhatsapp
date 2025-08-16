// docker/whatsapp-bridge/src/config/logger.js
const winston = require("winston");
const DailyRotateFile = require("winston-daily-rotate-file");
const path = require("path");
const fs = require("fs");

// Essayer plusieurs dossiers selon les permissions
const getLogDir = () => {
    const possibleDirs = ["./logs", "/tmp/whatsapp-logs"]; // hors Docker, privilégier ./logs

    for (const dir of possibleDirs) {
        try {
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true });
            }
            const testFile = path.join(dir, "test-write.log");
            fs.writeFileSync(testFile, "test");
            fs.unlinkSync(testFile);
            console.log(`✅ Using log directory: ${dir}`);
            return dir;
        } catch (error) {
            console.warn(`❌ Cannot use ${dir}:`, error.message);
        }
    }

    console.warn("⚠️ Using console logging only - no writable directory found");
    return null;
};

const logDir = getLogDir();

// Configuration des transports
const transports = [
    // Console (toujours disponible)
    new winston.transports.Console({
        format: winston.format.combine(
            winston.format.colorize(),
            winston.format.simple(),
        ),
    }),
];

// Ajouter les transports fichier seulement si on a un dossier utilisable
if (logDir) {
    transports.push(
        // Fichier rotatif pour tous les logs
        new DailyRotateFile({
            filename: path.join(logDir, "app-%DATE%.log"),
            datePattern: "YYYY-MM-DD",
            maxSize: "20m",
            maxFiles: "14d",
            handleExceptions: false, // Désactiver pour éviter les crashes
            format: winston.format.combine(
                winston.format.timestamp(),
                winston.format.json(),
            ),
        }),

        // Fichier spécial pour les erreurs
        new DailyRotateFile({
            filename: path.join(logDir, "error-%DATE%.log"),
            datePattern: "YYYY-MM-DD",
            level: "error",
            maxSize: "20m",
            maxFiles: "30d",
            handleExceptions: false,
            format: winston.format.combine(
                winston.format.timestamp(),
                winston.format.json(),
            ),
        }),

        // Fichier pour les sessions WhatsApp
        new DailyRotateFile({
            filename: path.join(logDir, "whatsapp-%DATE%.log"),
            datePattern: "YYYY-MM-DD",
            maxSize: "50m",
            maxFiles: "7d",
            handleExceptions: false,
            format: winston.format.combine(
                winston.format.timestamp(),
                winston.format.json(),
            ),
        })

        // NOTE: Les fichiers incoming-messages et outgoing-messages sont gérés 
        // par des loggers spécialisés séparés pour éviter les doublons
    );
}

// Créer le logger principal
const logger = winston.createLogger({
    level: process.env.LOG_LEVEL || "info",
    format: winston.format.combine(
        winston.format.timestamp({
            format: "YYYY-MM-DD HH:mm:ss",
        }),
        winston.format.errors({ stack: true }),
        winston.format.json(),
    ),
    transports,
    exitOnError: false, // Ne pas crasher sur erreur de log
});

// Créer des loggers spécialisés pour les messages
const createMessageLogger = (filename, logType) => {
    if (!logDir) return null;
    
    return winston.createLogger({
        level: 'info',
        format: winston.format.combine(
            winston.format.timestamp(),
            winston.format.json(),
        ),
        transports: [
            new DailyRotateFile({
                filename: path.join(logDir, filename),
                datePattern: "YYYY-MM-DD",
                maxSize: "100m",
                maxFiles: "30d",
                handleExceptions: false,
            })
        ],
        exitOnError: false,
    });
};

// Loggers spécialisés
const incomingLogger = createMessageLogger("incoming-messages-%DATE%.log", "incoming");
const outgoingLogger = createMessageLogger("outgoing-messages-%DATE%.log", "outgoing");

// Méthodes personnalisées
logger.api = (message, meta = {}) => logger.info(`[API] ${message}`, meta);
logger.whatsapp = (message, meta = {}) =>
    logger.info(`[WhatsApp] ${message}`, meta);
logger.session = (sessionId, message, meta = {}) =>
    logger.info(`[Session:${sessionId}] ${message}`, meta);

// Méthodes personnalisées
logger.api = (message, meta = {}) => logger.info(`[API] ${message}`, meta);
logger.whatsapp = (message, meta = {}) =>
    logger.info(`[WhatsApp] ${message}`, meta);
logger.session = (sessionId, message, meta = {}) =>
    logger.info(`[Session:${sessionId}] ${message}`, meta);

// Méthodes spécifiques pour les messages
logger.incomingMessage = (message, meta = {}) => {
    const logData = {
        ...meta,
        messageDirection: 'incoming',
        timestamp: new Date().toISOString()
    };
    
    // Log dans le fichier général
    logger.info(`📨 ${message}`, logData);
    
    // Log dans le fichier spécifique incoming si disponible
    if (incomingLogger) {
        incomingLogger.info(message, logData);
    }
};

logger.outgoingMessage = (message, meta = {}) => {
    const logData = {
        ...meta,
        messageDirection: 'outgoing',
        timestamp: new Date().toISOString()
    };
    
    // Log dans le fichier général
    logger.info(`📤 ${message}`, logData);
    
    // Log dans le fichier spécifique outgoing si disponible
    if (outgoingLogger) {
        outgoingLogger.info(message, logData);
    }
};

// Gérer les erreurs de winston lui-même
logger.on("error", (error) => {
    console.error("❌ Winston logging error:", error.message);
});

logger.logDir = logDir;

module.exports = logger;
