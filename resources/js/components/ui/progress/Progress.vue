<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import type { ProgressRootProps } from "reka-ui"
import { reactiveOmit } from "@vueuse/core"
import { ProgressIndicator, ProgressRoot } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<ProgressRootProps & {
  class?: HTMLAttributes["class"]
  indicatorClass?: HTMLAttributes["class"]
}>()

const delegatedProps = reactiveOmit(props, "class", "indicatorClass")
</script>

<template>
  <ProgressRoot
    data-slot="progress"
    v-bind="delegatedProps"
    :class="cn('relative h-2 w-full overflow-hidden rounded-full bg-primary/20', props.class)"
  >
    <ProgressIndicator
      data-slot="progress-indicator"
      :class="cn('h-full w-full flex-1 bg-primary transition-all', props.indicatorClass)"
      :style="`transform: translateX(-${100 - (props.modelValue ?? 0)}%)`"
    />
  </ProgressRoot>
</template>
