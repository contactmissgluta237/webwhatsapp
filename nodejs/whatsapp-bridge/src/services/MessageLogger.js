const logger = require("../config/logger");

class MessageLogger {
    static logIncomingMessage(event, data) {
        logger.incomingMessage(event, data);
    }

    static logOutgoingMessage(event, data) {
        logger.outgoingMessage(event, data);
    }

    static logWhatsApp(event, data) {
        logger.whatsapp(event, data);
    }

    static logError(event, data) {
        logger.error(event, data);
    }

    static logInfo(message, data) {
        logger.info(message, data);
    }

    static logWarning(event, data) {
        logger.warning(event, data);
    }

    static logDebug(message, data) {
        logger.debug(message, data);
    }
}

module.exports = MessageLogger;