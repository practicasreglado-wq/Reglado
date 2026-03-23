<template>
  <section class="about">
    <div class="about-images">
      <div class="img-large">
        <img src="@/assets/aboutimg2.png" alt="Descripcion imagen pequeña">
      </div>
    </div>

    <div class="about-content">
      <span class="about-kicker">Reglado Real State</span>
      <h2>Quiénes somos</h2>
      <div class="about-text">
        <p class="about-lead">
          En Reglado Real State trabajamos con un enfoque más exclusivo, orientado a clientes que saben exactamente qué inversión están buscando.
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
  gap: 96px;
  min-height: 100vh;
  background: transparent;
  padding: 60px 40px;
}

.about-images {
  position: relative;
  display: flex;
  align-items: flex-start;
}

.img-large {
  width: 420px;
  height: 500px;
  overflow: hidden;
  border-radius: 28px;
  margin-top: 120px;
  padding: 10px;
  background: rgba(200, 200, 200, 0.532);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  box-shadow:
    0 22px 48px rgba(28, 46, 88, 0.08),
    inset 0 1px 0 rgba(255, 255, 255, 0.55);
}

.about-images img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  border-radius: 22px;
}

.about-content {
  max-width: 700px;
  display: grid;
  gap: 18px;
}

.about-kicker {
  display: inline-flex;
  align-items: center;
  width: max-content;
  padding: 10px 20px;
  border-radius: 999px;
  background: linear-gradient(135deg, #172a5d 0%, #3654ae 100%);
  border: 1px solid rgba(244, 208, 120, 0.4);
  color: #f4d078;
  font-size: 1rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  box-shadow: 0 8px 16px rgba(23, 42, 93, 0.15);
  margin-bottom: 6px;
}

.about-content h2 {
  margin: 0;
  font-size: clamp(2.2rem, 6vw, 5rem);
  line-height: 1.05;
  color: #1f2f4d;
}

.about-text {
  position: relative;
  overflow: hidden;
  padding: 32px;
  border-radius: 28px;
  background: linear-gradient(180deg, #ffffff, #f7f9fc);
  border: 1px solid rgba(224, 219, 202, 0.9);
  box-shadow: 0 22px 48px rgba(28, 46, 88, 0.08);
}

.about-text::before,
.about-text::after {
  content: "";
  position: absolute;
  border-radius: 999px;
  pointer-events: none;
  opacity: 0.72;
}

.about-text::before {
  width: 180px;
  height: 180px;
  right: -60px;
  top: -80px;
  background: rgba(189, 155, 44, 0.08);
}

.about-text::after {
  width: 130px;
  height: 130px;
  left: -40px;
  bottom: -70px;
  background: rgba(32, 56, 107, 0.05);
}

.about-text p {
  position: relative;
  z-index: 1;
  margin: 0;
  font-size: clamp(0.92rem, 1.2vw, 1.15rem);
  line-height: 1.75;
  color: #4b5b78;
}

.about-lead {
  font-size: clamp(1.05rem, 1.45vw, 1.45rem) !important;
  font-weight: 700;
  color: #20386b !important;
  margin-bottom: 14px !important;
}

.about-highlights {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.about-card {
  padding: 22px 20px;
  border-radius: 22px;
  background: linear-gradient(180deg, #ffffff, #f8fafc);
  border: 1px solid rgba(202, 211, 224, 0.9);
  box-shadow: 0 16px 34px rgba(28, 46, 88, 0.08);
  transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
}

.about-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 22px 42px rgba(28, 46, 88, 0.12);
  border-color: rgba(189, 155, 44, 0.28);
}

.about-card__index {
  display: inline-flex;
  margin-bottom: 14px;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(189, 155, 44, 0.12);
  color: #9b7a1d;
  font-size: 0.74rem;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.about-card h3 {
  margin: 0 0 10px;
  font-size: 1.05rem;
  color: #1f2f4d;
}

.about-card p {
  margin: 0;
  font-size: 0.95rem;
  line-height: 1.65;
  color: #5b6b86;
}

.about-metrics {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
}

.about-metric {
  display: grid;
  gap: 6px;
  padding: 18px 20px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(202, 211, 224, 0.8);
}

.about-metric strong {
  color: #20386b;
  font-size: 1rem;
}

.about-metric span {
  color: #667792;
  font-size: 0.9rem;
  line-height: 1.5;
}

@media (max-width: 1024px) {
  .about {
    gap: 40px;
  }

  .img-large {
    width: 220px;
    height: 280px;
    margin-top: 60px;
    padding: 8px;
  }

  .about-content h2 {
    font-size: clamp(2rem, 5vw, 3.5rem);
  }

  .about-text p {
    font-size: clamp(1rem, 2vw, 1.25rem);
  }

  .about-highlights {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .about {
    flex-direction: column;
    text-align: center;
    gap: 40px;
  }

  .about-images {
    align-items: center;
  }

  .img-large {
    width: 220px;
    height: 280px;
    margin-top: 40px;
  }

  .about-content {
    max-width: 600px;
    justify-items: center;
  }

  .about-content h2 {
    font-size: clamp(1.9rem, 4.5vw, 2.5rem);
  }

  .about-text {
    padding: 24px;
  }

  .about-text p {
    font-size: clamp(0.95rem, 1.8vw, 1.1rem);
  }

  .about-metrics {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 480px) {
  .about {
    padding: 60px 20px;
  }

  .img-large {
    width: 170px;
    height: 230px;
    margin-top: 30px;
  }

  .about-content h2 {
    font-size: clamp(1.5rem, 8vw, 1.9rem);
  }

  .about-text {
    padding: 20px;
    border-radius: 22px;
  }

  .about-text p {
    font-size: 0.9rem;
    line-height: 1.6;
  }
}
</style>
