<template>
  <span
    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
    :style="badgeStyle"
  >
    {{ label || "Inconnu" }}
  </span>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
  label: { type: String, default: "Inconnu" },

  color: { type: String, default: "#6B7280" },
});

// Fond très clair pour le badge et couleur de texte lisible.
const badgeStyle = computed(() => {
  const hex = (props.color || "#6B7280").trim();

  const toRgb = (h) => {
    const m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(h);
    if (!m) return { r: 107, g: 114, b: 128 }; // gray-500
    return {
      r: parseInt(m[1], 16),
      g: parseInt(m[2], 16),
      b: parseInt(m[3], 16),
    };
  };
  const { r, g, b } = toRgb(hex);

  const bg = `rgba(${r}, ${g}, ${b}, 0.12)`;
  const fg = `rgb(${r}, ${g}, ${b})`;
  return { backgroundColor: bg, color: fg };
});
</script>
