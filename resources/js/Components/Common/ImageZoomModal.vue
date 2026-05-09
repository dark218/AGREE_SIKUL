<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    imageUrl: {
        type: String,
        default: null,
    },
    alt: {
        type: String,
        default: 'Image',
    },
});

const emit = defineEmits(['close']);

const scale = ref(1);
const position = ref({ x: 0, y: 0 });
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const imageRef = ref(null);
const naturalSize = ref({ width: 0, height: 0 });

// Taille calculée de l'image basée sur le zoom
const imageSize = computed(() => {
    const baseWidth = Math.min(naturalSize.value.width || 600, window.innerWidth * 0.8);
    const baseHeight = Math.min(naturalSize.value.height || 400, window.innerHeight * 0.7);
    return {
        width: baseWidth * scale.value,
        height: baseHeight * scale.value,
    };
});

const close = () => {
    resetZoom();
    emit('close');
};

const resetZoom = () => {
    scale.value = 1;
    position.value = { x: 0, y: 0 };
};

const zoomIn = () => {
    if (scale.value < 5) {
        scale.value = Math.min(scale.value + 0.25, 5);
    }
};

const zoomOut = () => {
    if (scale.value > 0.25) {
        scale.value = Math.max(scale.value - 0.25, 0.25);
        // Reset position if zooming out to prevent image from being off-screen
        if (scale.value <= 1) {
            position.value = { x: 0, y: 0 };
        }
    }
};

const handleWheel = (e) => {
    e.preventDefault();
    if (e.deltaY < 0) {
        zoomIn();
    } else {
        zoomOut();
    }
};

const startDrag = (e) => {
    isDragging.value = true;
    dragStart.value = {
        x: e.clientX - position.value.x,
        y: e.clientY - position.value.y,
    };
};

const onDrag = (e) => {
    if (isDragging.value) {
        position.value = {
            x: e.clientX - dragStart.value.x,
            y: e.clientY - dragStart.value.y,
        };
    }
};

const endDrag = () => {
    isDragging.value = false;
};

const handleKeydown = (e) => {
    if (props.show) {
        if (e.key === 'Escape') {
            close();
        } else if (e.key === '+' || e.key === '=') {
            zoomIn();
        } else if (e.key === '-') {
            zoomOut();
        } else if (e.key === '0') {
            resetZoom();
        }
    }
};

const onImageLoad = (e) => {
    naturalSize.value = {
        width: e.target.naturalWidth,
        height: e.target.naturalHeight,
    };
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
        resetZoom();
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="show"
                class="image-zoom-overlay"
                @click.self="close"
                @wheel="handleWheel"
            >
                <div class="image-zoom-container">
                    <!-- Close button -->
                    <button class="zoom-close-btn" @click="close" title="Fermer (Echap)">
                        <i class="fa fa-times"></i>
                    </button>

                    <!-- Zoom controls -->
                    <div class="zoom-controls">
                        <button class="zoom-btn" @click="zoomOut" title="Zoom arrière (-)">
                            <i class="fa fa-minus"></i>
                        </button>
                        <span class="zoom-level">{{ Math.round(scale * 100) }}%</span>
                        <button class="zoom-btn" @click="zoomIn" title="Zoom avant (+)">
                            <i class="fa fa-plus"></i>
                        </button>
                        <button class="zoom-btn" @click="resetZoom" title="Réinitialiser (0)">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </div>

                    <!-- Image -->
                    <div
                        class="image-wrapper"
                        :class="{ 'is-dragging': isDragging }"
                        @mousedown="startDrag"
                        @mousemove="onDrag"
                        @mouseup="endDrag"
                        @mouseleave="endDrag"
                    >
                        <img
                            ref="imageRef"
                            :src="imageUrl"
                            :alt="alt"
                            class="zoomed-image"
                            :style="{
                                width: imageSize.width + 'px',
                                height: 'auto',
                                maxWidth: 'none',
                                maxHeight: 'none',
                                transform: `translate(${position.x}px, ${position.y}px)`,
                            }"
                            draggable="false"
                            @load="onImageLoad"
                        />
                    </div>

                    <!-- Instructions -->
                    <div class="zoom-instructions">
                        <span>Molette: Zoom</span>
                        <span>Clic + Glisser: Déplacer</span>
                        <span>Echap: Fermer</span>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.image-zoom-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.9);
    z-index: 9999;
    overflow: auto;
}

.image-zoom-container {
    position: relative;
    min-width: 100%;
    min-height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 20px 120px;
}

.zoom-close-btn {
    position: fixed;
    top: 20px;
    right: 20px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.3s;
    z-index: 10;
}

.zoom-close-btn:hover {
    background-color: rgba(255, 255, 255, 0.3);
}

.zoom-controls {
    position: fixed;
    bottom: 80px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    align-items: center;
    gap: 10px;
    background-color: rgba(0, 0, 0, 0.7);
    padding: 10px 20px;
    border-radius: 30px;
    z-index: 10;
}

.zoom-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background-color 0.3s;
}

.zoom-btn:hover {
    background-color: rgba(255, 255, 255, 0.3);
}

.zoom-level {
    color: white;
    font-size: 14px;
    min-width: 50px;
    text-align: center;
}

.image-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: grab;
}

.image-wrapper.is-dragging {
    cursor: grabbing;
}

.zoomed-image {
    object-fit: contain;
    transition: width 0.15s ease-out, transform 0.05s ease-out;
    user-select: none;
}

.zoom-instructions {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 20px;
    color: rgba(255, 255, 255, 0.6);
    font-size: 12px;
    z-index: 10;
}

/* Transitions */
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}
</style>
