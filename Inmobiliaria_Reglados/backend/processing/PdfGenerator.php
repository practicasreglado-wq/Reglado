<?php
declare(strict_types=1);

use Dompdf\Dompdf;

class PdfGenerator
{
    private string $storageDir;

    public function __construct(string $storageDir)
    {
        $this->storageDir = rtrim($storageDir, '/\\');

        if ($this->storageDir === '') {
            throw new RuntimeException('Directorio de textos inválido');
        }

        if (!is_dir($this->storageDir)) {
            if (!mkdir($this->storageDir, 0750, true) && !is_dir($this->storageDir)) {
                throw new RuntimeException('No se pudo crear el directorio de textos');
            }
            @chmod($this->storageDir, 0750);
        }
    }

    public function generateDocuments(array $data, int $propertyId): array
    {
        $ficha = $data['ficha_web'] ?? [];
        $dossier = $data['dossier_inversion'] ?? [];

        $propertyType = $this->slugifyFilenamePart($ficha['tipo_propiedad'] ?? null);
        $city = $this->slugifyFilenamePart($ficha['ciudad'] ?? null);
        $zone = $this->slugifyFilenamePart($ficha['zona'] ?? null);

        $ndaFile = "nda_{$propertyType}_{$city}_{$zone}.pdf";
        $loiFile = "loi_{$propertyType}_{$city}_{$zone}.pdf";

        $ndaPath = $this->storageDir . DIRECTORY_SEPARATOR . $ndaFile;
        $loiPath = $this->storageDir . DIRECTORY_SEPARATOR . $loiFile;

        $ndaHtml = $this->buildNdaHtml($ficha, $dossier);
        $loiHtml = $this->buildLoiHtml($ficha, $dossier);

        $this->createPdf($ndaHtml, $ndaPath);
        $this->createPdf($loiHtml, $loiPath);

        return [
            'confidentiality_file' => $ndaFile,
            'intention_file' => $loiFile,
        ];
    }

    private function createPdf(string $html, string $path): void
    {
        try {
            error_log('PDF START -> ' . $path);

            $dompdf = new Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $output = $dompdf->output();

            if (!$output) {
                throw new RuntimeException('PDF vacío');
            }

            if (file_put_contents($path, $output) === false) {
                throw new RuntimeException('No se pudo guardar PDF');
            }

            error_log('PDF OK -> ' . $path);
        } catch (Throwable $e) {
            error_log('ERROR PDF: ' . $e->getMessage());
            throw $e;
        }
    }

    private function buildNdaHtml(array $ficha, array $dossier): string
    {
        $city = $this->value($ficha['ciudad'] ?? null);
        $date = $this->formatDate();
        $propertyType = $this->value($ficha['tipo_propiedad'] ?? null);
        $address = $this->value($ficha['direccion'] ?? $dossier['ubicacion_completa'] ?? null);
        $zone = $this->value($ficha['zona'] ?? null);
        $price = $this->formatCurrency($ficha['precio'] ?? $dossier['precio_inicial'] ?? null);

        $html = $this->baseHtmlStart('Acuerdo de Confidencialidad');

        $html .= '<div class="document">';
        $html .= '<h1>ACUERDO DE CONFIDENCIALIDAD</h1>';
        $html .= '<p class="intro"><strong>En ' . $city . ', a ' . $date . '.</strong></p>';

        $html .= '<p>
            El presente Acuerdo de Confidencialidad regula el acceso, tratamiento y uso de la información
            facilitada en relación con un activo inmobiliario respecto del cual la parte receptora manifiesta
            interés profesional, inversor o comercial.
        </p>';

        $html .= '<div class="box">';
        $html .= '<h2>Identificación del activo</h2>';
        $html .= '<p><strong>Tipo de propiedad:</strong> ' . $propertyType . '</p>';
        $html .= '<p><strong>Dirección / referencia:</strong> ' . $address . '</p>';
        $html .= '<p><strong>Zona:</strong> ' . $zone . '</p>';
        $html .= '<p><strong>Ciudad:</strong> ' . $city . '</p>';
        $html .= '<p><strong>Precio orientativo:</strong> ' . $price . '</p>';
        $html .= '</div>';

        $html .= '<h2>Cláusulas</h2>';

        $html .= '<h3>Primera.- Objeto</h3>';
        $html .= '<p>
            La parte receptora se compromete a tratar como confidencial toda la información, documentación,
            datos, antecedentes, informes, condiciones económicas, urbanísticas, jurídicas, técnicas o comerciales
            que se le faciliten en relación con el activo identificado.
        </p>';

        $html .= '<h3>Segunda.- Información confidencial</h3>';
        $html .= '<p>
            Tendrá la consideración de información confidencial toda aquella que sea comunicada por cualquier medio,
            ya sea escrito, verbal, digital o documental, incluyendo la propia existencia de negociaciones,
            conversaciones, condiciones de la operación y documentación remitida.
        </p>';

        $html .= '<h3>Tercera.- Obligación de reserva</h3>';
        $html .= '<p>
            La parte receptora no podrá divulgar, ceder, comunicar o poner a disposición de terceros la información
            confidencial sin autorización previa y expresa de la parte facilitadora.
        </p>';

        $html .= '<h3>Cuarta.- Limitación de uso</h3>';
        $html .= '<p>
            La información confidencial únicamente podrá utilizarse para valorar una posible operación de adquisición,
            inversión, intermediación o colaboración sobre el activo, quedando prohibido cualquier uso distinto.
        </p>';

        $html .= '<h3>Quinta.- Exclusiones</h3>';
        $html .= '<p>
            No tendrá carácter confidencial aquella información que sea de dominio público, que ya obrara legítimamente
            en poder de la parte receptora con anterioridad o cuya divulgación venga impuesta por norma legal o resolución administrativa o judicial.
        </p>';

        $html .= '<h3>Sexta.- Duración</h3>';
        $html .= '<p>
            Las obligaciones asumidas en virtud del presente acuerdo permanecerán vigentes durante un plazo de dos años
            desde la fecha de su aceptación, con independencia de que finalmente se formalice o no operación alguna.
        </p>';

        $html .= '<h3>Séptima.- Devolución o destrucción de la información</h3>';
        $html .= '<p>
            A requerimiento de la parte facilitadora, la parte receptora devolverá o destruirá la documentación recibida,
            sin conservar copias, extractos o reproducciones, salvo obligación legal de conservación.
        </p>';

        $html .= '<h3>Octava.- Naturaleza del acuerdo</h3>';
        $html .= '<p>
            El presente acuerdo no implica obligación de compraventa, reserva, exclusividad ni compromiso de cierre,
            limitándose a regular la confidencialidad de la información intercambiada.
        </p>';

        $html .= '<h3>Novena.- Jurisdicción</h3>';
        $html .= '<p>
            Para cualquier controversia derivada de este acuerdo, las partes se someten a los Juzgados y Tribunales
            que resulten competentes conforme a Derecho.
        </p>';

        $html .= '<div class="signature-block">';
        $html .= '<div class="signature-line"></div>';
        $html .= '<p><strong>Firma</strong></p>';
        $html .= '<p>Nombre / representación:</p>';
        $html .= '<p>Fecha:</p>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= $this->baseHtmlEnd();

        return $html;
    }

    private function buildLoiHtml(array $ficha, array $dossier): string
    {
        $city = $this->value($ficha['ciudad'] ?? null);
        $date = $this->formatDate();
        $propertyType = $this->value($ficha['tipo_propiedad'] ?? null);
        $address = $this->value($ficha['direccion'] ?? $dossier['ubicacion_completa'] ?? null);
        $zone = $this->value($ficha['zona'] ?? null);
        $surface = $this->formatSurface($dossier['superficie_construida'] ?? $ficha['metros_cuadrados'] ?? null);
        $price = $this->formatCurrency($dossier['precio_inicial'] ?? $ficha['precio'] ?? null);
        $minClose = $this->formatCurrency($dossier['precio_minimo_cierre'] ?? null);

        $html = $this->baseHtmlStart('Carta de Intenciones');

        $html .= '<div class="document">';
        $html .= '<h1>CARTA DE INTENCIONES E INTERÉS (LOI) NO VINCULANTE</h1>';
        $html .= '<p class="intro"><strong>En ' . $city . ', a ' . $date . '.</strong></p>';

        $html .= '<p>
            Por medio del presente documento, la parte interesada manifiesta su interés en estudiar, valorar y negociar
            la posible adquisición del siguiente activo inmobiliario, sin que ello implique por sí mismo obligación
            contractual de cierre o compraventa.
        </p>';

        $html .= '<div class="box">';
        $html .= '<h2>Identificación del activo</h2>';
        $html .= '<p><strong>Tipo de propiedad:</strong> ' . $propertyType . '</p>';
        $html .= '<p><strong>Ubicación:</strong> ' . $address . '</p>';
        $html .= '<p><strong>Zona:</strong> ' . $zone . '</p>';
        $html .= '<p><strong>Ciudad:</strong> ' . $city . '</p>';
        $html .= '<p><strong>Superficie de referencia:</strong> ' . $surface . '</p>';
        $html .= '<p><strong>Precio orientativo:</strong> ' . $price . '</p>';
        if (($dossier['precio_minimo_cierre'] ?? null) !== null) {
            $html .= '<p><strong>Precio mínimo de cierre:</strong> ' . $minClose . '</p>';
        }
        $html .= '</div>';

        $html .= '<p>
            Tras un análisis preliminar de la información facilitada, la parte interesada considera que el activo puede
            resultar de interés estratégico, quedando cualquier decisión final sujeta al análisis técnico, jurídico,
            económico, registral, urbanístico y comercial correspondiente.
        </p>';

        $html .= '<h2>Cláusulas</h2>';

        $html .= '<h3>Primera.- Naturaleza no vinculante</h3>';
        $html .= '<p>
            La presente carta constituye una manifestación de interés no vinculante y no genera obligación de comprar,
            vender, transmitir o adquirir el activo, salvo en lo relativo a confidencialidad, buena fe y reserva de información cuando proceda.
        </p>';

        $html .= '<h3>Segunda.- Due diligence</h3>';
        $html .= '<p>
            Cualquier eventual operación quedará condicionada al resultado satisfactorio de la revisión documental,
            jurídica, técnica, urbanística, fiscal, comercial y financiera del activo.
        </p>';

        $html .= '<h3>Tercera.- Confidencialidad</h3>';
        $html .= '<p>
            La información recibida tendrá carácter confidencial y quedará sujeta, en su caso, al acuerdo de confidencialidad
            aplicable entre las partes.
        </p>';

        $html .= '<h3>Cuarta.- Buena fe negociadora</h3>';
        $html .= '<p>
            En caso de continuar las conversaciones, las partes actuarán de buena fe durante la fase preliminar de análisis,
            sin que ello implique obligación de alcanzar acuerdo definitivo.
        </p>';

        $html .= '<h3>Quinta.- Gastos y tributos</h3>';
        $html .= '<p>
            Cada parte asumirá sus propios gastos, honorarios profesionales, asesoramiento externo y costes de análisis
            derivados de la eventual operación, salvo pacto posterior por escrito.
        </p>';

        $html .= '<h3>Sexta.- Jurisdicción</h3>';
        $html .= '<p>
            Para cualquier discrepancia relativa a la interpretación o efectos de esta carta, las partes se someten a los
            Juzgados y Tribunales que resulten competentes conforme a Derecho.
        </p>';

        if (!empty($dossier['observaciones']) && is_array($dossier['observaciones'])) {
            $html .= '<div class="box">';
            $html .= '<h2>Observaciones relevantes</h2><ul>';
            foreach ($dossier['observaciones'] as $item) {
                if ($item === null || trim((string) $item) === '') {
                    continue;
                }
                $html .= '<li>' . htmlspecialchars((string) $item) . '</li>';
            }
            $html .= '</ul></div>';
        }

        $html .= '<div class="signature-block">';
        $html .= '<div class="signature-line"></div>';
        $html .= '<p><strong>Firma</strong></p>';
        $html .= '<p>Nombre / representación:</p>';
        $html .= '<p>Fecha:</p>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= $this->baseHtmlEnd();

        return $html;
    }

    private function baseHtmlStart(string $title): string
    {
        return '<html><head><meta charset="utf-8"><style>
            body {
                font-family: Arial, sans-serif;
                color: #111;
                font-size: 12px;
                line-height: 1.55;
                margin: 0;
                padding: 0;
            }
            .document {
                padding: 38px 42px;
            }
            h1 {
                font-size: 22px;
                text-align: center;
                margin: 0 0 24px 0;
                color: #1f3c88;
                text-transform: uppercase;
            }
            h2 {
                font-size: 15px;
                margin: 24px 0 10px;
                color: #1f3c88;
                border-bottom: 1px solid #cfd8ea;
                padding-bottom: 4px;
            }
            h3 {
                font-size: 13px;
                margin: 16px 0 6px;
                color: #222;
            }
            p {
                margin: 0 0 10px 0;
                text-align: justify;
            }
            .intro {
                text-align: left;
                margin-bottom: 18px;
            }
            .box {
                border: 1px solid #d8deea;
                background: #f8fbff;
                padding: 12px 14px;
                margin: 18px 0;
                border-radius: 6px;
            }
            .box p {
                margin-bottom: 7px;
            }
            ul {
                margin: 8px 0 0 18px;
                padding: 0;
            }
            li {
                margin-bottom: 6px;
            }
            .signature-block {
                margin-top: 48px;
                page-break-inside: avoid;
            }
            .signature-line {
                width: 240px;
                border-top: 1px solid #333;
                margin: 36px 0 10px 0;
            }
            .footer-note {
                margin-top: 30px;
                font-size: 10px;
                color: #666;
            }
        </style><title>' . htmlspecialchars($title) . '</title></head><body>';
    }

    private function baseHtmlEnd(): string
    {
        return '</body></html>';
    }

    private function value(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'No disponible';
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        if (is_array($value)) {
            return implode(', ', array_map(fn($item) => (string) $item, $value));
        }

        return htmlspecialchars((string) $value);
    }

    private function formatCurrency(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'No disponible';
        }

        return number_format((float) $value, 2, ',', '.') . ' €';
    }

    private function formatSurface(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'No disponible';
        }

        return number_format((float) $value, 0, ',', '.') . ' m²';
    }

    private function formatDate(): string
    {
        return date('d/m/Y');
    }

    private function slugifyFilenamePart(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '') {
            return 'sin_dato';
        }

        $replacements = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
            'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a',
            'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
            'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
            'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o',
            'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
            'Ñ' => 'n', 'Ç' => 'c',
        ];

        $text = strtr($text, $replacements);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '_', $text);
        $text = trim((string) $text, '_');

        if ($text === '') {
            return 'sin_dato';
        }

        return $text;
    }
}