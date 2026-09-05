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

# Configure pnpm to allow esbuild builds via .npmrc
RUN echo "allow-build-scripts=esbuild" > .npmrc

# Install dependencies
RUN pnpm install

# Copy source code
COPY frontend/ .

# Generate Nuxt types
RUN pnpm run postinstall || true

EXPOSE 3000

CMD ["pnpm", "run", "dev"]
