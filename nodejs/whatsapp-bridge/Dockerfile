FROM node:18-slim

# Install basic dependencies (skip chromium for now due to network limitations)
RUN apt-get update && apt-get install -y \
    ca-certificates \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Tell Puppeteer to skip chromium download (will need manual installation in production)
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true

# Set working directory
WORKDIR /app

# Install app dependencies
COPY package*.json ./
RUN npm config set strict-ssl false && npm install --production && npm cache clean --force

# Copy app source
COPY . .

# Create necessary directories
RUN mkdir -p /app/sessions /app/logs

# Set permissions
RUN chown -R node:node /app
USER node

# Expose port
EXPOSE 3000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD node healthcheck.js

# Start the application
CMD ["node", "src/server.js"]
