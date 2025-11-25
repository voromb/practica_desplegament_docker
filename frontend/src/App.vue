<!-- src/App.vue -->
<template>
  <div id="app">
    <ItemsList />
  </div>
</template>

<script>
import ItemsList from './components/ItemsList.vue';

export default {
  name: 'App',
  components: {
    ItemsList
  }
};
</script>

<style>
/* Puedes agregar estilos globales aquí */
</style>
