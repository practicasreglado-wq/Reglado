<template>
  <footer class="site-footer">
    <div class="container footer-layout">
      <div class="footer-main">
        <h3>Reglado Group</h3>
        <p>Grupo empresarial en consultoria, energia, tecnologia, inmuebles e inversion.</p>
      </div>

      <div class="bottom">
        <div class="small">© {{ year }} Reglado Group. Todos los derechos reservados.</div>
        <div class="small">Transparencia · Rigor tecnico · Acompanamiento</div>
      </div>
    </div>
  </footer>
</template>

<script setup>
const year = new Date().getFullYear();
</script>

<style scoped>
.site-footer {
  border-top: 1px solid rgba(39, 61, 92, 0.15);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(243, 247, 254, 0.9));
  min-height: 170px;
  display: flex;
  align-items: stretch;
  padding-inline: clamp(1rem, 2.5vw, 2rem);
}

.container {
  width: min(1160px, 100%);
  margin: 0 auto;
}

.footer-layout {
  width: 100%;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 1.4rem 0 1rem;
  gap: 1rem;
}

.footer-main h3 {
  margin: 0;
  color: #273d5c;
  font-size: 1.2rem;
}

.footer-main p {
  margin: 0.35rem 0 0;
  color: #5a6981;
}

.bottom {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  width: 100%;
  padding-top: 0.9rem;
  border-top: 1px solid rgba(39, 61, 92, 0.14);
}

.small {
  font-size: 0.9rem;
  color: #6a7990;
}

@media (max-width: 760px) {
  .bottom {
    flex-direction: column;
    gap: 0.45rem;
  }
}
</style>
