# Multi-stage Docker build for Journalism Workshop Platform

# Stage 1: Build Frontend
FROM node:18-alpine AS frontend-builder
WORKDIR /app/frontend
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ ./
RUN npm run build

# Stage 2: Backend Setup
FROM node:18-alpine AS backend-setup
WORKDIR /app/backend
COPY backend/package*.json ./
RUN npm ci --only=production
COPY backend/ ./

# Stage 3: Production
FROM node:18-alpine
WORKDIR /app

# Install PM2 for process management
RUN npm install -g pm2

# Copy backend
COPY --from=backend-setup /app/backend ./backend

# Copy frontend build
COPY --from=frontend-builder /app/frontend/.next ./frontend/.next
COPY --from=frontend-builder /app/frontend/public ./frontend/public
COPY --from=frontend-builder /app/frontend/package*.json ./frontend/
COPY --from=frontend-builder /app/frontend/next.config.ts ./frontend/

# Install frontend production dependencies
WORKDIR /app/frontend
RUN npm ci --only=production

# Create uploads directory
WORKDIR /app/backend
RUN mkdir -p uploads

# Expose ports
EXPOSE 5000 3000

# Copy PM2 ecosystem file
COPY ecosystem.config.js /app/

# Start both services with PM2
WORKDIR /app
CMD ["pm2-runtime", "start", "ecosystem.config.js"]
