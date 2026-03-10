<template>
  <PortalHero
    title="Portal Corporativo"
    subtitle="Accede rapidamente a las plataformas internas y servicios clave de la empresa."
  />

  <PortalGrid :portals="portals" />

  <HelpSection
    title="Necesitas ayuda?"
    description="Contacta con el equipo de sistemas para acceso o restablecimiento de credenciales."
  />
</template>

<script setup>
import HelpSection from "../components/portal/HelpSection.vue";
import PortalGrid from "../components/portal/PortalGrid.vue";
import PortalHero from "../components/portal/PortalHero.vue";

const portals = [
  {
    title: "Intranet",
    description: "Comunicaciones internas y documentos.",
    href: "https://intranet.tuempresa.com",
  },
  {
    title: "RRHH",
    description: "Gestion de personal, ausencias y nominas.",
    href: "https://rrhh.tuempresa.com",
  },
  {
    title: "Soporte",
    description: "Tickets tecnicos y estado de incidencias.",
    href: "https://soporte.tuempresa.com",
  },
  {
    title: "CRM",
    description: "Clientes, ventas y seguimiento comercial.",
    href: "https://crm.tuempresa.com",
  },
];
</script>
