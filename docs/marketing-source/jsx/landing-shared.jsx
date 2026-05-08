// Shared tokens + primitives for «يلا حجيز» landing page variations

const YH = {
  // Brand palette — unified
  green: '#0BA84A',
  greenDeep: '#067A36',
  greenSoft: '#D9F5E3',
  greenGlow: '#22D26A',
  ink: '#0F1A14',
  navy: '#0A1A2F',
  navyDeep: '#050E1C',
  cream: '#F6FBF5',
  white: '#FFFFFF',
  amber: '#FFB800',
  coral: '#FF6B5B',
  // Typography
  fonts: {
    ar: '"Cairo", "Tajawal", "Noto Kufi Arabic", system-ui, sans-serif',
    arDisplay: '"Cairo", "Tajawal", system-ui, sans-serif',
    en: '"Outfit", "Inter", system-ui, sans-serif',
    enDisplay: '"Space Grotesk", "Outfit", system-ui, sans-serif',
    mono: '"JetBrains Mono", ui-monospace, monospace',
  },
};

// Logo mark — bouncing ball + check
function YHMark({ size = 64, fg = '#fff', bg = YH.green, radius = 0.24 }) {
  const r = size * radius;
  return (
    <svg width={size} height={size} viewBox="0 0 100 100" style={{ display: 'block', flexShrink: 0 }}>
      <rect x="0" y="0" width="100" height="100" rx={r * 100 / size} fill={bg} />
      <path d="M24 26 L44 54 L44 76 L56 76 L56 54 L76 26"
        stroke={fg} strokeWidth="11" strokeLinecap="round" strokeLinejoin="round" fill="none"/>
      <path d="M62 50 L72 60 L88 38"
        stroke={fg} strokeWidth="9" strokeLinecap="round" strokeLinejoin="round" fill="none"/>
    </svg>
  );
}

// Wordmark
function YHWordmark({ size = 1, fg = YH.ink, accent = YH.green, lang = 'ar' }) {
  if (lang === 'ar') {
    return (
      <div style={{
        fontFamily: YH.fonts.arDisplay, fontWeight: 900, fontSize: 32 * size,
        color: fg, letterSpacing: -0.5, direction: 'rtl', lineHeight: 1,
      }}>
        يلا <span style={{ color: accent }}>حجيز</span>
      </div>
    );
  }
  return (
    <div style={{
      fontFamily: YH.fonts.enDisplay, fontWeight: 800, fontSize: 28 * size,
      color: fg, letterSpacing: -0.02 + 'em', lineHeight: 1,
    }}>
      yalla <span style={{ color: accent }}>hjeez</span>
    </div>
  );
}

function YHLogoLockup({ size = 1, fg = YH.ink, accent = YH.green, markBg, markFg = '#fff', stacked = false }) {
  return (
    <div style={{
      display: 'inline-flex', alignItems: 'center', gap: 14 * size,
      flexDirection: stacked ? 'column' : 'row',
    }}>
      <YHMark size={48 * size} bg={markBg || accent} fg={markFg}/>
      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-start', gap: 2 * size }}>
        <YHWordmark size={size * 0.85} fg={fg} accent={accent}/>
        <div style={{
          fontFamily: YH.fonts.en, fontWeight: 600, fontSize: 9 * size,
          color: fg, opacity: 0.55, letterSpacing: 2.5, textTransform: 'uppercase',
        }}>yalla hjeez</div>
      </div>
    </div>
  );
}

// Phone mockup placeholder (for hero)
function YHPhoneMock({ width = 280, theme = 'light', children }) {
  const height = width * 2.05;
  const isDark = theme === 'dark';
  return (
    <div style={{
      width, height, position: 'relative', borderRadius: width * 0.13,
      background: isDark ? '#1C1917' : '#0F1A14',
      padding: width * 0.025, boxShadow: '0 30px 80px rgba(11,168,74,0.25), 0 12px 32px rgba(15,26,20,0.18)',
    }}>
      <div style={{
        width: '100%', height: '100%', borderRadius: width * 0.105,
        background: isDark ? '#0C0A09' : '#FAFAF9',
        overflow: 'hidden', position: 'relative',
      }}>
        {/* Dynamic island */}
        <div style={{
          position: 'absolute', top: 10, left: '50%', transform: 'translateX(-50%)',
          width: width * 0.32, height: width * 0.08, background: '#000', borderRadius: 100, zIndex: 2,
        }}/>
        {children}
      </div>
    </div>
  );
}

// Stripe placeholder for missing photos
function YHStripe({ w = '100%', h = '100%', label = 'photo', color = YH.green, dark = false }) {
  return (
    <div style={{
      width: w, height: h, position: 'relative', overflow: 'hidden',
      background: dark
        ? `repeating-linear-gradient(135deg, rgba(34,210,106,0.18) 0 8px, rgba(34,210,106,0.06) 8px 16px)`
        : `repeating-linear-gradient(135deg, ${color}1a 0 8px, ${color}08 8px 16px)`,
      border: `1px dashed ${dark ? 'rgba(34,210,106,0.4)' : color + '55'}`,
      display: 'flex', alignItems: 'center', justifyContent: 'center',
      fontFamily: YH.fonts.mono, fontSize: 10, color: dark ? 'rgba(217,245,227,0.7)' : color,
      letterSpacing: '0.1em', textTransform: 'uppercase', borderRadius: 'inherit',
    }}>{label}</div>
  );
}

// Animated ball icon (CSS bounce)
function YHBall({ size = 24, color = YH.green }) {
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <circle cx="12" cy="12" r="10" fill={color}/>
      <path d="M12 2 L12 22 M2 12 L22 12 M5 5 L19 19 M19 5 L5 19" stroke="#fff" strokeWidth="0.8" opacity="0.5"/>
      <circle cx="12" cy="12" r="10" stroke="#000" strokeWidth="0.5" opacity="0.15" fill="none"/>
    </svg>
  );
}

// Common copy (bilingual)
const YH_COPY = {
  ar: {
    nav: ['الرئيسية', 'المميزات', 'كيف بيشتغل', 'الأسعار', 'التواصل'],
    heroTag: 'تطبيق حجز الملاعب رقم ١ بسوريا',
    heroH1: ['دق احجزلي', 'بـ ٣٠ ثانية بس'],
    heroSub: 'أكتر من ٢,٠٠٠ ملعب بكل المحافظات. لاقي ملعب فاضي، شوف الأسعار، وادفع من التطبيق — كل شي بضغطة وحدة.',
    cta1: 'حمّل التطبيق',
    cta2: 'كيف بيشتغل',
    stats: [
      { num: '2K+', label: 'ملعب' },
      { num: '50K+', label: 'لاعب' },
      { num: '14', label: 'محافظة' },
      { num: '4.9', label: 'تقييم' },
    ],
    features: [
      { icon: '⚡', title: 'حجز فوري', desc: 'احجز ملعبك بثواني بدون مكالمات أو رسايل.' },
      { icon: '📍', title: 'لاقي حواليك', desc: 'خريطة كاملة لكل الملاعب القريبة منك مع التقييمات.' },
      { icon: '💳', title: 'دفع آمن', desc: 'سيرياتيل كاش، MTN كاش، أو دفع نقدي عند الوصول.' },
      { icon: '👥', title: 'كوّن فريقك', desc: 'ادعو أصحابك، نظّم مباريات، وشارك بالبطولات.' },
      { icon: '🏆', title: 'بطولات', desc: 'سجّل ببطولات منطقتك واربح جوايز قيّمة.' },
      { icon: '⭐', title: 'تقييمات حقيقية', desc: 'شوف تقييمات لاعبين فعليين قبل ما تحجز.' },
    ],
    steps: [
      { n: '01', title: 'دوّر عن ملعبك', desc: 'فلتر حسب الرياضة، السعر، المنطقة، التوقيت' },
      { n: '02', title: 'اختار التوقيت', desc: 'شوف الأوقات المتاحة بالتقويم وحدّد ساعتك' },
      { n: '03', title: 'ادفع وأكّد', desc: 'ادفع من المحفظة أو نقد، ووصلك تأكيد فوراً' },
      { n: '04', title: 'يلا للملعب', desc: 'أوصل، اعرض رمز الحجز، والعب' },
    ],
    pricing: [
      { name: 'لاعب', price: 'مجاناً', desc: 'لكل اللاعبين', features: ['حجز غير محدود', 'فلترة وبحث', 'محفظة رقمية', 'تقييمات وإشعارات'] },
      { name: 'فريق', price: '٢٥,٠٠٠', unit: 'ل.س / شهر', desc: 'للأندية والفرق', features: ['كل مميزات اللاعب', 'إدارة الفريق', 'دردشة جماعية', 'تقارير شهرية'], featured: true },
      { name: 'مالك ملعب', price: 'حسب الإيراد', desc: 'لأصحاب الملاعب', features: ['لوحة تحكم كاملة', 'تقويم ذكي', 'دفع تلقائي', 'دعم مباشر ٢٤/٧'] },
    ],
    testimonials: [
      { name: 'فادي صالح', role: 'لاعب كرة قدم · دمشق', text: 'صرت احجز ملعبي كل أسبوع بدون عناء. التطبيق وفّرلي وقت كتير.', rating: 5 },
      { name: 'لينا خوري', role: 'مالكة نادي · حلب', text: 'منذ اشتركنا، ارتفع الإشغال ٤٠٪. لوحة التحكم عملية كتير.', rating: 5 },
      { name: 'رامي عبود', role: 'كابتن فريق · حمص', text: 'أحلى شي إنو منقدر نلم الفريق ومنحجز كلنا سوا. تطبيق خطير.', rating: 5 },
    ],
    faq: [
      { q: 'كيف بقدر احجز ملعب؟', a: 'حمّل التطبيق، سجّل دخول، دوّر عن الملعب، اختار التوقيت، وادفع — كل شي بأقل من دقيقة.' },
      { q: 'شو طرق الدفع المتاحة؟', a: 'سيرياتيل كاش، MTN كاش، تحويل بنكي، أو دفع نقدي عند الوصول للملعب.' },
      { q: 'بقدر ألغي الحجز؟', a: 'أكيد. الإلغاء قبل ٤٨ ساعة استرداد كامل، وقبل ٢٤ ساعة استرداد ٥٠٪.' },
      { q: 'هل في تطبيق لمالكي الملاعب؟', a: 'نعم، عنا لوحة تحكم خاصة لإدارة الحجوزات، الأسعار، والتقارير المالية.' },
      { q: 'بأي محافظات متوفّر التطبيق؟', a: 'كل المحافظات الـ ١٤، مع تركيز على دمشق، حلب، حمص، حماة، اللاذقية، وطرطوس.' },
    ],
    download: 'حمّل التطبيق',
    bookNow: 'احجز هلق',
    learnMore: 'اعرف أكتر',
    contact: 'تواصل معنا',
    footer: {
      about: 'يلا حجيز هي منصة حجز الملاعب الأولى بسوريا. مهمتنا نسهّل عليك تلعب الرياضة الي بتحبها.',
      links: ['الرئيسية', 'المميزات', 'الأسعار', 'البطولات', 'مالك ملعب', 'تواصل'],
      copyright: '© ٢٠٢٦ يلا حجيز · كل الحقوق محفوظة',
    },
  },
};

Object.assign(window, { YH, YHMark, YHWordmark, YHLogoLockup, YHPhoneMock, YHStripe, YHBall, YH_COPY });
