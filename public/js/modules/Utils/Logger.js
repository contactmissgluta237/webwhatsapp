/**
 * Logger centralisé avec niveaux et formatage
 */
export class Logger {
    constructor(context = 'App') {
        this.context = context;
        this.isEnabled = true;
    }

    _formatMessage(level, message, data = null) {
        const timestamp = new Date().toLocaleTimeString();
        const prefix = this._getLevelPrefix(level);
        const contextInfo = `[${timestamp}] ${prefix} ${this.context}:`;
        
        return { contextInfo, message, data };
    }

    _getLevelPrefix(level) {
        const prefixes = {
            info: 'ℹ️',
            warn: '⚠️',
            error: '❌',
            debug: '🔍',
            success: '✅'
        };
        return prefixes[level] || '📝';
    }

    info(message, data = null) {
        if (!this.isEnabled) return;
        const formatted = this._formatMessage('info', message, data);
        console.log(formatted.contextInfo, formatted.message, formatted.data || '');
    }

    warn(message, data = null) {
        if (!this.isEnabled) return;
        const formatted = this._formatMessage('warn', message, data);
        console.warn(formatted.contextInfo, formatted.message, formatted.data || '');
    }

    error(message, data = null) {
        if (!this.isEnabled) return;
        const formatted = this._formatMessage('error', message, data);
        console.error(formatted.contextInfo, formatted.message, formatted.data || '');
    }

    debug(message, data = null) {
        if (!this.isEnabled) return;
        const formatted = this._formatMessage('debug', message, data);
        console.debug(formatted.contextInfo, formatted.message, formatted.data || '');
    }

    success(message, data = null) {
        if (!this.isEnabled) return;
        const formatted = this._formatMessage('success', message, data);
        console.log(formatted.contextInfo, formatted.message, formatted.data || '');
    }

    disable() {
        this.isEnabled = false;
    }

    enable() {
        this.isEnabled = true;
    }
}