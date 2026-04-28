<template>
  <section id="about-us" class="about">
    <div class="about-images">
      <div class="img-large">
        <img src="@/assets/aboutimg2.png" alt="Descripcion imagen pequeña">
      </div>
    </div>

    <div class="about-content">
      <span class="about-kicker">Reglado Real Estate</span>
      <h2>Quiénes somos</h2>
      <div class="about-text">
        <p class="about-lead">
          En Reglado Real Estate trabajamos con un enfoque más exclusivo, orientado a clientes que saben exactamente qué inversión están buscando.
        </p>
      </div>

      <div class="about-highlights">
        <article class="about-card">
          <span class="about-card__index">01</span>
          <h3>Búsqueda muy precisa</h3>
          <p>Cada filtro está pensado para que el cliente llegue directamente al tipo de inmueble de inversión que realmente busca.</p>
        </article>

        <article class="about-card">
          <span class="about-card__index">02</span>
          <h3>Cliente con intención real</h3>
          <p>Cuando alguien pide más información de un inmueble, suele hacerlo porque el activo ya encaja de forma seria en su objetivo de compra.</p>
        </article>

        <article class="about-card">
          <span class="about-card__index">03</span>
          <h3>Activos con encaje inversor</h3>
          <p>Priorizamos oportunidades con sentido estratégico para perfiles que valoran rentabilidad, seguridad y decisión rápida.</p>
        </article>
      </div>

      <div class="about-metrics">
        <div class="about-metric">
          <strong>Filtro</strong>
          <span>Más afinado y selectivo</span>
        </div>
        <div class="about-metric">
          <strong>Interés</strong>
          <span>Consultas con intención real</span>
        </div>
        <div class="about-metric">
          <strong>Inversión</strong>
          <span>Activos con mejor encaje</span>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: "AboutUs",
};
</script>

<style scoped>
.about {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-lg);
  min-height: 80vh;
  background: #e9e9e9; /* Blanco más oscuro requested */
  padding: var(--spacing-md) var(--spacing-md); /* Added balanced vertical padding */
}

.about-images {
  position: relative;
  display: flex;
  justify-content: center;
  flex: 1;
  max-width: 500px;
}

.img-large {
  width: 100%;
  aspect-ratio: 4 / 5;
  overflow: hidden;
  border-radius: 28px;
  padding: 10px;
  background: rgba(200, 200, 200, 0.4);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  box-shadow: 0 22px 48px rgba(28, 46, 88, 0.08);
}

.about-images img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  border-radius: 22px;
}

.about-content {
  flex: 1.2;
  max-width: 700px;
  display: grid;
  gap: var(--spacing-sm);
}

.about-kicker {
  display: inline-flex;
  align-items: center;
  width: max-content;
  padding: 8px 16px;
  border-radius: 999px;
  background: linear-gradient(135deg, #172a5d 0%, #3654ae 100%);
  border: 1px solid rgba(244, 208, 120, 0.4);
  color: #f4d078;
  font-size: 0.85rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.about-content h2 {
  margin: 0;
  font-size: var(--font-size-h2);
  line-height: 1.1;
  color: #1f2f4d;
}

.about-text {
  position: relative;
  padding: var(--spacing-md);
  border-radius: 24px;
  background: linear-gradient(180deg, #ffffff, #f7f9fc);
  border: 1px solid rgba(224, 219, 202, 0.8);
  box-shadow: 0 20px 40px rgba(28, 46, 88, 0.06);
}

.about-text p {
  position: relative;
  z-index: 1;
  margin: 0;
  font-size: var(--font-size-base);
  line-height: 1.7;
  color: #4b5b78;
}

.about-lead {
  font-weight: 800;
  color: #20386b !important;
  margin-bottom: 12px !important;
}

.about-highlights {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-sm);
}

.about-card {
  padding: var(--spacing-sm);
  border-radius: 20px;
  background: #fff;
  border: 1px solid rgba(202, 211, 224, 0.8);
  box-shadow: 0 12px 24px rgba(28, 46, 88, 0.05);
  transition: transform 0.3s ease;
}

.about-card:hover {
  transform: translateY(-4px);
  border-color: #bd9b2c;
}

.about-card__index {
  display: inline-flex;
  margin-bottom: 12px;
  padding: 4px 8px;
  border-radius: 10px;
  background: rgba(189, 155, 44, 0.1);
  color: #9b7a1d;
  font-size: 0.7rem;
  font-weight: 800;
}

.about-card h3 {
  margin: 0 0 8px;
  font-size: 1rem;
  color: #1f2f4d;
}

.about-card p {
  margin: 0;
  font-size: 0.85rem;
  line-height: 1.5;
  color: #5b6b86;
}

.about-metrics {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--spacing-xs);
}

.about-metric {
  display: grid;
  gap: 8px;
  padding: 20px 18px;
  border-radius: 20px;
  background: #ffffff;
  border: 1px solid rgba(189, 155, 44, 0.2); /* Subtle Gold border */
  box-shadow: 0 10px 25px rgba(28, 46, 88, 0.08);
  transition: all 0.4s var(--motion-ease-premium);
  position: relative;
  overflow: hidden;
}

.about-metric::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  background: linear-gradient(to bottom, #bd9b2c, #f4d078);
  opacity: 0.8;
}

.about-metric:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 35px rgba(28, 46, 88, 0.12);
  border-color: rgba(189, 155, 44, 0.5);
}

.about-metric strong {
  color: #1a2545;
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: -0.01em;
}

.about-metric span {
  color: #5b6b86;
  font-size: 0.85rem;
  line-height: 1.4;
}

/* =========================
   BREAKPOINTS
   ========================= */

@media (max-width: 1024px) {
  .about {
    gap: var(--spacing-md);
  }

  .about-highlights {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .about {
    flex-direction: column;
    text-align: center;
    gap: var(--spacing-md);
  }

  .about-images {
    width: 60%;
    margin: 0 auto;
  }

  .about-content {
    justify-items: center;
  }

  .about-metrics {
    grid-template-columns: 1fr;
    width: 100%;
  }

  .about-kicker {
    align-self: center;
  }
}

@media (max-width: 480px) {
  .about {
    padding: var(--spacing-md) var(--spacing-sm);
  }

  .about-images {
    width: 80%;
  }

  .about-text {
    padding: var(--spacing-sm);
  }
}
</style>
