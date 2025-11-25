<!-- src/components/ItemsList.vue -->
<template>
  <div>
    <h1>Items List</h1>
    <ul>
      <li v-for="item in items" :key="item.id">
        {{ item.name }} - {{ item.description }}
      </li>
    </ul>
    <form @submit.prevent="addItem">
      <input v-model="newItem.name" placeholder="Name" required />
      <input v-model="newItem.description" placeholder="Description" required />
      <button type="submit">Add Item</button>
    </form>
  </div>
</template>

<script>
export default {
  name: 'ItemsList',
  data() {
    return {
      items: [],
      newItem: {
        name: '',
        description: ''
      }
    };
  },
  methods: {
    fetchItems() {
      // Fetch items from API
      fetch('http://localhost:8000/api/items')
        .then(response => response.json())
        .then(data => {
          this.items = data;
        });
    },
    addItem() {
      // Add a new item
      fetch('http://localhost:8000/api/items', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(this.newItem)
      })
        .then(response => response.json())
        .then(() => {
          this.fetchItems(); // Refresh the list
          this.newItem = { name: '', description: '' }; // Reset input fields
        });
    }
  },
  mounted() {
    this.fetchItems();
  }
};
</script>

<style scoped>
/* Puedes agregar estilos aquí */
</style>
