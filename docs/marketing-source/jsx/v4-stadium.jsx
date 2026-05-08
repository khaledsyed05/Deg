// Variation 4 — DARK STADIUM
// Night-mode hero, neon glows, stadium energy, animated feel

function V4Stadium() {
  const C = YH_COPY.ar;
  return (
    <div style={{
      width: '100%', minHeight: 2400, background: YH.navyDeep, direction: 'rtl',
      fontFamily: YH.fonts.ar, color: YH.white, overflow: 'hidden', position: 'relative',
    }}>
      {/* NAV */}
      <nav style={{
        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
        padding: '24px 56px', position: 'sticky', top: 0,
        background: 'rgba(5,14,28,0.7)', backdropFilter: 'blur(12px)',
        borderBottom: '1px solid rgba(34,210,106,0.15)', zIndex: 10,
      }}>
        <YHLogoLockup size={0.8} fg={YH.white} accent={YH.greenGlow} markBg={YH.greenGlow} markFg={YH.navyDeep}/>
        <div style={{ display: 'flex', gap: 32 }}>
          {C.nav.map((n, i) => (
            <a key={i} style={{ fontSize: 13, fontWeight: 600, color: YH.white, opacity: i === 0 ? 1 : 0.6 }}>{n}</a>
          ))}
        </div>
        <button style={{
          background: YH.greenGlow, color: YH.navyDeep, border: 'none',
          padding: '12px 22px', borderRadius: 999, fontWeight: 800, fontSize: 13,
          fontFamily: YH.fonts.ar, cursor: 'pointer',
          boxShadow: `0 0 24px rgba(34,210,106,0.5)`,
        }}>{C.download}</button>
      </nav>

      {/* HERO — stadium night */}
      <section style={{
        position: 'relative', padding: '100px 56px 80px', minHeight: 720,
        overflow: 'hidden',
      }}>
        {/* glow */}
        <div style={{
          position: 'absolute', top: '20%', left: '50%', transform: 'translateX(-50%)',
          width: 800, height: 800, borderRadius: 999,
          background: 'radial-gradient(circle, rgba(34,210,106,0.25), transparent 60%)',
        }}/>
        {/* grid pattern */}
        <div style={{
          position: 'absolute', inset: 0,
          backgroundImage: 'linear-gradient(rgba(34,210,106,0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(34,210,106,0.08) 1px, transparent 1px)',
          backgroundSize: '60px 60px',
        }}/>

        <div style={{ position: 'relative', display: 'grid', gridTemplateColumns: '1.2fr 1fr', gap: 48, alignItems: 'center' }}>
          <div>
            <div style={{
              display: 'inline-flex', alignItems: 'center', gap: 10,
              background: 'rgba(34,210,106,0.1)', border: '1px solid rgba(34,210,106,0.3)',
              color: YH.greenGlow, padding: '8px 16px', borderRadius: 999,
              fontSize: 12, fontWeight: 700, marginBottom: 32, fontFamily: YH.fonts.mono, letterSpacing: 1,
            }}>
              <span style={{ width: 6, height: 6, borderRadius: 99, background: YH.greenGlow,
                boxShadow: '0 0 8px rgba(34,210,106,0.8)' }}/>
              مباشر · ٢,٠٠٠ ملعب الآن
            </div>
            <h1 style={{
              fontSize: 112, fontWeight: 900, lineHeight: 0.95, letterSpacing: -3, margin: 0,
            }}>
              يلا<br/>
              <span style={{
                color: YH.greenGlow,
                textShadow: '0 0 40px rgba(34,210,106,0.5)',
              }}>حجيز.</span><br/>
              <span style={{ opacity: 0.7, fontSize: 80 }}>والملعب لك.</span>
            </h1>
            <p style={{ fontSize: 18, lineHeight: 1.7, opacity: 0.7, marginTop: 28, maxWidth: 520 }}>
              {C.heroSub}
            </p>
            <div style={{ display: 'flex', gap: 12, marginTop: 36 }}>
              <button style={{
                background: YH.greenGlow, color: YH.navyDeep, border: 'none',
                padding: '18px 32px', borderRadius: 999, fontWeight: 800, fontSize: 15,
                fontFamily: YH.fonts.ar, cursor: 'pointer',
                boxShadow: '0 0 32px rgba(34,210,106,0.45)',
              }}>{C.cta1} ↓</button>
              <button style={{
                background: 'rgba(255,255,255,0.05)', color: YH.white, border: '1px solid rgba(255,255,255,0.2)',
                padding: '17px 32px', borderRadius: 999, fontWeight: 700, fontSize: 15,
                fontFamily: YH.fonts.ar, cursor: 'pointer', backdropFilter: 'blur(8px)',
              }}>{C.cta2} →</button>
            </div>
          </div>

          {/* Phone with glow */}
          <div style={{ display: 'flex', justifyContent: 'center', position: 'relative' }}>
            <div style={{
              position: 'absolute', width: 380, height: 380, borderRadius: 999,
              background: 'radial-gradient(circle, rgba(34,210,106,0.4), transparent 70%)',
              filter: 'blur(40px)',
            }}/>
            <div style={{ position: 'relative' }}>
              <YHPhoneMock width={300} theme="dark">
                <PhoneHomeContentDark/>
              </YHPhoneMock>
              <div style={{
                position: 'absolute', top: 80, right: -40,
                background: 'rgba(255,255,255,0.08)', border: '1px solid rgba(34,210,106,0.4)',
                color: YH.white, padding: '12px 16px', borderRadius: 14,
                fontSize: 12, fontWeight: 700, backdropFilter: 'blur(8px)',
              }}>
                <div style={{ fontSize: 10, opacity: 0.6, marginBottom: 2 }}>تم الحجز</div>
                <div style={{ color: YH.greenGlow }}>✓ ملعب الفيحاء · ٧:٠٠م</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* STATS — neon */}
      <section style={{ padding: '64px 56px', borderTop: '1px solid rgba(255,255,255,0.08)', borderBottom: '1px solid rgba(255,255,255,0.08)' }}>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 32, textAlign: 'center' }}>
          {C.stats.map((s, i) => (
            <div key={i}>
              <div style={{
                fontSize: 64, fontWeight: 900, letterSpacing: -2, lineHeight: 1,
                color: YH.greenGlow,
                textShadow: '0 0 20px rgba(34,210,106,0.4)',
              }}>{s.num}</div>
              <div style={{ fontSize: 13, opacity: 0.6, marginTop: 10, textTransform: 'uppercase', letterSpacing: 1.5, fontFamily: YH.fonts.mono }}>{s.label}</div>
            </div>
          ))}
        </div>
      </section>

      {/* FEATURES */}
      <section style={{ padding: '100px 56px' }}>
        <div style={{ textAlign: 'center', maxWidth: 700, marginInline: 'auto', marginBottom: 56 }}>
          <div style={{ fontFamily: YH.fonts.mono, fontSize: 12, letterSpacing: 2, color: YH.greenGlow, marginBottom: 16, textTransform: 'uppercase' }}>// المميزات</div>
          <h2 style={{ fontSize: 64, fontWeight: 900, letterSpacing: -2, margin: 0, lineHeight: 1 }}>
            بُني <span style={{ color: YH.greenGlow }}>للاعبين</span>.<br/>صُنع للسرعة.
          </h2>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 16 }}>
          {C.features.map((f, i) => (
            <div key={i} style={{
              padding: 32, borderRadius: 20,
              background: 'linear-gradient(180deg, rgba(34,210,106,0.06), rgba(34,210,106,0.01))',
              border: '1px solid rgba(34,210,106,0.18)',
              position: 'relative', overflow: 'hidden',
            }}>
              <div style={{
                width: 52, height: 52, background: 'rgba(34,210,106,0.12)',
                border: '1px solid rgba(34,210,106,0.3)',
                borderRadius: 12, display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: 24, marginBottom: 24,
              }}>{f.icon}</div>
              <div style={{ fontSize: 20, fontWeight: 800, marginBottom: 10 }}>{f.title}</div>
              <div style={{ fontSize: 14, lineHeight: 1.65, opacity: 0.65 }}>{f.desc}</div>
              <div style={{ fontFamily: YH.fonts.mono, fontSize: 11, color: YH.greenGlow, opacity: 0.4, marginTop: 20, letterSpacing: 1 }}>0{i+1} / 06</div>
            </div>
          ))}
        </div>
      </section>

      {/* HOW IT WORKS */}
      <section style={{ padding: '100px 56px', borderTop: '1px solid rgba(255,255,255,0.08)' }}>
        <div style={{ textAlign: 'center', marginBottom: 56 }}>
          <div style={{ fontFamily: YH.fonts.mono, fontSize: 12, letterSpacing: 2, color: YH.greenGlow, marginBottom: 16, textTransform: 'uppercase' }}>// كيف بيشتغل</div>
          <h2 style={{ fontSize: 64, fontWeight: 900, letterSpacing: -2, margin: 0, lineHeight: 1 }}>أربع خطوات. ثلاثين ثانية.</h2>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 16 }}>
          {C.steps.map((s, i) => (
            <div key={i} style={{
              padding: 28, borderRadius: 18,
              background: 'rgba(255,255,255,0.03)',
              border: '1px solid rgba(255,255,255,0.08)',
            }}>
              <div style={{
                width: 48, height: 48, borderRadius: 999, background: YH.greenGlow, color: YH.navyDeep,
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontWeight: 900, fontSize: 16, marginBottom: 24,
                boxShadow: '0 0 24px rgba(34,210,106,0.4)',
              }}>{s.n}</div>
              <div style={{ fontSize: 19, fontWeight: 700, marginBottom: 8 }}>{s.title}</div>
              <div style={{ fontSize: 13, opacity: 0.6, lineHeight: 1.6 }}>{s.desc}</div>
            </div>
          ))}
        </div>
      </section>

      {/* TESTIMONIALS */}
      <section style={{ padding: '100px 56px', borderTop: '1px solid rgba(255,255,255,0.08)' }}>
        <div style={{ textAlign: 'center', marginBottom: 56 }}>
          <div style={{ fontFamily: YH.fonts.mono, fontSize: 12, letterSpacing: 2, color: YH.greenGlow, marginBottom: 16, textTransform: 'uppercase' }}>// آراء العملاء</div>
          <h2 style={{ fontSize: 64, fontWeight: 900, letterSpacing: -2, margin: 0, lineHeight: 1 }}>كلام من <span style={{ color: YH.greenGlow }}>الأرض</span>.</h2>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 16 }}>
          {C.testimonials.map((t, i) => (
            <div key={i} style={{
              padding: 32, borderRadius: 20,
              background: 'rgba(255,255,255,0.03)',
              border: '1px solid rgba(255,255,255,0.1)',
              display: 'flex', flexDirection: 'column', gap: 18,
            }}>
              <div style={{ display: 'flex', gap: 4 }}>
                {[...Array(t.rating)].map((_, j) => <span key={j} style={{ color: YH.greenGlow, fontSize: 16 }}>★</span>)}
              </div>
              <div style={{ fontSize: 16, lineHeight: 1.65, opacity: 0.9 }}>«{t.text}»</div>
              <div style={{ display: 'flex', gap: 12, alignItems: 'center', marginTop: 'auto', paddingTop: 18, borderTop: '1px solid rgba(255,255,255,0.08)' }}>
                <div style={{ width: 42, height: 42, borderRadius: 999, background: YH.greenGlow, color: YH.navyDeep, display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 800 }}>{t.name[0]}</div>
                <div>
                  <div style={{ fontWeight: 700, fontSize: 14 }}>{t.name}</div>
                  <div style={{ fontSize: 12, opacity: 0.6 }}>{t.role}</div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* PRICING */}
      <section style={{ padding: '100px 56px', borderTop: '1px solid rgba(255,255,255,0.08)' }}>
        <div style={{ textAlign: 'center', marginBottom: 56 }}>
          <div style={{ fontFamily: YH.fonts.mono, fontSize: 12, letterSpacing: 2, color: YH.greenGlow, marginBottom: 16, textTransform: 'uppercase' }}>// الأسعار</div>
          <h2 style={{ fontSize: 64, fontWeight: 900, letterSpacing: -2, margin: 0, lineHeight: 1 }}>اختار خطّتك.</h2>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 16, maxWidth: 1100, marginInline: 'auto' }}>
          {C.pricing.map((p, i) => (
            <div key={i} style={{
              padding: 36, borderRadius: 24,
              background: p.featured ? 'linear-gradient(180deg, rgba(34,210,106,0.18), rgba(34,210,106,0.05))' : 'rgba(255,255,255,0.03)',
              border: p.featured ? '1px solid rgba(34,210,106,0.5)' : '1px solid rgba(255,255,255,0.1)',
              boxShadow: p.featured ? '0 0 48px rgba(34,210,106,0.2)' : 'none',
            }}>
              <div style={{ fontFamily: YH.fonts.mono, fontSize: 12, letterSpacing: 1.5, opacity: 0.7, marginBottom: 16, textTransform: 'uppercase', color: p.featured ? YH.greenGlow : YH.white }}>0{i+1} · {p.name}</div>
              <div style={{ display: 'flex', alignItems: 'baseline', gap: 6, marginBottom: 8 }}>
                <span style={{ fontSize: 48, fontWeight: 900, letterSpacing: -1 }}>{p.price}</span>
                {p.unit && <span style={{ fontSize: 13, opacity: 0.6 }}>{p.unit}</span>}
              </div>
              <div style={{ fontSize: 14, opacity: 0.65, marginBottom: 28, paddingBottom: 24, borderBottom: '1px solid rgba(255,255,255,0.1)' }}>{p.desc}</div>
              {p.features.map((f, j) => (
                <div key={j} style={{ display: 'flex', gap: 10, alignItems: 'center', marginBottom: 12, fontSize: 14 }}>
                  <span style={{ color: YH.greenGlow, fontWeight: 800 }}>✓</span> {f}
                </div>
              ))}
              <button style={{
                width: '100%', marginTop: 24, padding: '14px 20px', borderRadius: 999,
                background: p.featured ? YH.greenGlow : 'transparent',
                color: p.featured ? YH.navyDeep : YH.white,
                border: p.featured ? 'none' : '1px solid rgba(255,255,255,0.2)',
                fontWeight: 800, fontSize: 14, fontFamily: YH.fonts.ar, cursor: 'pointer',
              }}>ابدأ الآن</button>
            </div>
          ))}
        </div>
      </section>

      {/* FAQ */}
      <section style={{ padding: '100px 56px', borderTop: '1px solid rgba(255,255,255,0.08)' }}>
        <div style={{ maxWidth: 800, marginInline: 'auto' }}>
          <div style={{ textAlign: 'center', marginBottom: 56 }}>
            <div style={{ fontFamily: YH.fonts.mono, fontSize: 12, letterSpacing: 2, color: YH.greenGlow, marginBottom: 16, textTransform: 'uppercase' }}>// أسئلة متكررة</div>
            <h2 style={{ fontSize: 64, fontWeight: 900, letterSpacing: -2, margin: 0, lineHeight: 1 }}>اسأل، منجاوب.</h2>
          </div>
          {C.faq.map((f, i) => (
            <details key={i} style={{
              background: 'rgba(255,255,255,0.03)', padding: '20px 28px', borderRadius: 14,
              border: '1px solid rgba(255,255,255,0.08)', marginBottom: 12, cursor: 'pointer',
            }}>
              <summary style={{ fontWeight: 700, fontSize: 16, listStyle: 'none', display: 'flex', justifyContent: 'space-between' }}>
                {f.q} <span style={{ color: YH.greenGlow, fontSize: 20 }}>+</span>
              </summary>
              <div style={{ fontSize: 14, opacity: 0.7, lineHeight: 1.7, marginTop: 12 }}>{f.a}</div>
            </details>
          ))}
        </div>
      </section>

      {/* FINAL CTA */}
      <section style={{ padding: '120px 56px', textAlign: 'center', position: 'relative', overflow: 'hidden' }}>
        <div style={{
          position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%, -50%)',
          width: 800, height: 800, borderRadius: 999,
          background: 'radial-gradient(circle, rgba(34,210,106,0.3), transparent 60%)',
        }}/>
        <div style={{ position: 'relative' }}>
          <h2 style={{
            fontSize: 128, fontWeight: 900, letterSpacing: -4, lineHeight: 0.92, margin: 0,
          }}>
            يلا.<br/>
            <span style={{ color: YH.greenGlow, textShadow: '0 0 40px rgba(34,210,106,0.5)' }}>حجيز.</span>
          </h2>
          <p style={{ fontSize: 19, opacity: 0.7, marginTop: 24 }}>الملعب فاضي. والوقت عم يمشي.</p>
          <div style={{ display: 'flex', gap: 12, justifyContent: 'center', marginTop: 40 }}>
            <button style={{
              background: YH.greenGlow, color: YH.navyDeep, border: 'none', padding: '18px 32px',
              borderRadius: 999, fontWeight: 800, fontSize: 15, fontFamily: YH.fonts.ar, cursor: 'pointer',
              boxShadow: '0 0 40px rgba(34,210,106,0.5)',
            }}>App Store ↓</button>
            <button style={{
              background: 'rgba(255,255,255,0.05)', color: YH.white, border: '1px solid rgba(255,255,255,0.2)',
              padding: '17px 32px', borderRadius: 999, fontWeight: 700, fontSize: 15, fontFamily: YH.fonts.ar, cursor: 'pointer', backdropFilter: 'blur(8px)',
            }}>Google Play ↓</button>
          </div>
        </div>
      </section>

      {/* FOOTER */}
      <footer style={{ padding: '48px 56px', borderTop: '1px solid rgba(255,255,255,0.08)' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 24 }}>
          <YHLogoLockup size={0.7} fg={YH.white} accent={YH.greenGlow} markBg={YH.greenGlow} markFg={YH.navyDeep}/>
          <div style={{ display: 'flex', gap: 24, fontSize: 13, opacity: 0.6 }}>
            {['الشروط','الخصوصية','تواصل','مساعدة'].map((l,i) => <a key={i}>{l}</a>)}
          </div>
          <div style={{ fontSize: 12, opacity: 0.5, fontFamily: YH.fonts.mono, letterSpacing: 1 }}>{C.footer.copyright}</div>
        </div>
      </footer>
    </div>
  );
}

// Phone content for dark
function PhoneHomeContentDark() {
  return (
    <div style={{ padding: '52px 16px 16px', height: '100%', fontFamily: YH.fonts.ar, direction: 'rtl', background: '#0C0A09', color: '#FAFAF9', fontSize: 11 }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
        <div>
          <div style={{ fontSize: 11, opacity: 0.6 }}>أهلاً، كريم</div>
          <div style={{ fontSize: 16, fontWeight: 800 }}>وين رح تلعب الليلة؟</div>
        </div>
        <div style={{ width: 32, height: 32, borderRadius: 999, background: YH.greenGlow }}/>
      </div>
      <div style={{ background: '#1C1917', borderRadius: 12, padding: '10px 14px', fontSize: 11, opacity: 0.6, marginBottom: 18 }}>
        🔍 دوّر عن ملعب…
      </div>
      <div style={{ display: 'flex', gap: 8, marginBottom: 16 }}>
        {['كرة قدم','تنس','سلة','طائرة'].map((s, i) => (
          <div key={i} style={{
            padding: '7px 12px', borderRadius: 99, fontSize: 10, fontWeight: 700,
            background: i === 0 ? YH.greenGlow : '#1C1917',
            color: i === 0 ? '#0C0A09' : '#FAFAF9', whiteSpace: 'nowrap',
          }}>{s}</div>
        ))}
      </div>
      <div style={{ fontSize: 12, fontWeight: 700, marginBottom: 10 }}>ملاعب قريبة منك</div>
      {[
        { n: 'ملعب الفيحاء', l: 'دمشق · المزة', p: '٦٠ ل.س / س' },
        { n: 'تشرين الرياضية', l: 'دمشق · برزة', p: '٨٠ ل.س / س' },
        { n: 'ملعب الجلاء', l: 'دمشق · المالكي', p: '٥٠ ل.س / س' },
      ].map((g, i) => (
        <div key={i} style={{
          background: '#1C1917', border: '1px solid #292524', borderRadius: 12,
          padding: 10, display: 'flex', gap: 10, marginBottom: 8, alignItems: 'center',
        }}>
          <div style={{ width: 48, height: 48, borderRadius: 8, background: 'rgba(34,210,106,0.15)', flexShrink: 0 }}/>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ fontWeight: 800, fontSize: 11 }}>{g.n}</div>
            <div style={{ fontSize: 9, opacity: 0.6, marginTop: 2 }}>{g.l}</div>
            <div style={{ fontSize: 10, color: YH.greenGlow, fontWeight: 700, marginTop: 2 }}>{g.p}</div>
          </div>
          <div style={{ fontSize: 9, color: YH.amber, fontWeight: 700 }}>★ ٤.٨</div>
        </div>
      ))}
    </div>
  );
}

Object.assign(window, { V4Stadium, PhoneHomeContentDark });
