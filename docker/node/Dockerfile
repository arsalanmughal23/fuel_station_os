FROM node:20-alpine

# Install build tools
RUN apk add --no-cache git python3 make g++

# Set working directory
WORKDIR /app

# Copy package files
COPY frontend/package*.json ./
COPY frontend/pnpm-lock.yaml ./

# Install pnpm
RUN npm install -g pnpm

# Install dependencies (allow updating lockfile for development)
RUN pnpm install

# Copy source code
COPY frontend/ .

# Generate Nuxt types
RUN pnpm run postinstall || true

EXPOSE 3000

CMD ["pnpm", "run", "dev"]
