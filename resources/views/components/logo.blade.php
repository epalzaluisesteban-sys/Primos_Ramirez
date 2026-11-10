{{--
    Logo "Sello LPR" de Los Primos Ramírez: el mismo en todo el sistema (login, menú y favicon).
    El aro y las letras usan currentColor (verde, con su variante para modo oscuro).
    Uso: <x-logo class="w-20 h-20" />
    Si cambias el dibujo, actualiza también public/favicon.svg.
--}}
<svg viewBox="0 0 120 120" role="img" aria-label="Los Primos Ramírez"
     {{ $attributes->merge(['class' => 'text-emerald-800 dark:text-emerald-400 shrink-0']) }}>
    <circle cx="60" cy="60" r="44" fill="none" stroke="currentColor" stroke-width="4"/>
    <circle cx="60" cy="60" r="37" fill="none" stroke="currentColor" stroke-width="1.5"/>
    <path d="M60 42C60 34 66 30 72 30C72 37 66 42 60 42Z" fill="#65a30d"/>
    <text x="60" y="68" text-anchor="middle" font-size="24" font-weight="800" fill="currentColor"
          font-family="Manrope, sans-serif" letter-spacing="1">LPR</text>
    <circle cx="38" cy="82" r="3" fill="#f97316"/>
    <circle cx="60" cy="86" r="3" fill="#f97316"/>
    <circle cx="82" cy="82" r="3" fill="#f97316"/>
</svg>
