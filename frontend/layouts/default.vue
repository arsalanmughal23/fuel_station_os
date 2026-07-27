<template>
  <div>
    <header>
      <nav>
        <NuxtLink to="/">Home</NuxtLink>
      </nav>
    </header>
    <main>
      <slot />
    </main>
  </div>
</template>

<style scoped>
header {
  padding: 1rem;
  background: #2c3e50;
  color: white;
}

nav a {
  color: white;
  text-decoration: none;
  margin-right: 1rem;
}
</style>
