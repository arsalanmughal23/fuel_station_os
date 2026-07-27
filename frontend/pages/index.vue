<template>
  <div class="container">
    <h1>Fuel Station OS</h1>
    <p>Welcome to the Fuel Station Management System</p>
    
    <div class="api-test">
      <h2>API Test</h2>
      <button @click="testApi">Test API Connection</button>
      <div v-if="apiResult" class="result">
        <pre>{{ JSON.stringify(apiResult, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const apiResult = ref<any>(null)

const testApi = async () => {
  try {
    const response = await $fetch(`${config.public.apiBaseUrl}/health`)
    apiResult.value = response
  } catch (error) {
    apiResult.value = { error: 'Failed to connect to API', details: error }
  }
}
</script>

<style scoped>
.container {
  padding: 2rem;
  max-width: 800px;
  margin: 0 auto;
}

.api-test {
  margin-top: 2rem;
  padding: 1rem;
  border: 1px solid #ccc;
  border-radius: 8px;
}

.result {
  margin-top: 1rem;
  padding: 1rem;
  background: #f5f5f5;
  border-radius: 4px;
}
</style>