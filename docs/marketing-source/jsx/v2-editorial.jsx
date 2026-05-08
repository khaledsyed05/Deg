// Variation 2 — EDITORIAL MAGAZINE
// Asymmetric grid, oversized serif-feel display type, photo-essay layout, refined

function V2Editorial() {
  const C = YH_COPY.ar;
  return (
    <div style={{
      width: '100%', minHeight: 2400, background: YH.white, direction: 'rtl',
      fontFamily: YH.fonts.ar, color: YH.ink, overflow: 'hidden',
    }}>
      {/* NAV — minimal */}
      <nav style={{
        display: 'grid', gridTemplateColumns: '1fr auto 1fr', alignItems: 'center',
        padding: '32px 64px', borderBottom: `1px solid ${YH.ink}`,
      }}>
        <YHLogoLockup size={0.75}/>
        <div style={{ display: 'flex', gap: 28, justifyContent: 'center' }}>
          {C.nav.map((n, i) => <a key={i} style={{ fontSize: 13, fontWeight: 600, color: YH.ink, opacity: 0.85 }}>{n}</a>)}
        </div>
        <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 12 }}>
          <div style={{ fontSize: 12, color: YH.ink, opacity: 0.5, fontFamily: YH.fonts.mono, letterSpacing: 1.5 }}>ISSUE 01 · 2026</div>
        </div>
      </nav>

      {/* HERO — magazine cover */}
      <section style={{ padding: '64px 64px 80px', borderBottom: `1px solid ${YH.ink}` }}>
        <div style={{ display: 'grid', gridTemplateColumns: '1.1fr 1fr', gap: 48 }}>
          <div>
            <div style={{ fontFamily: YH.fonts.mono, fontSize: 12, letterSpacing: 2, marginBottom: 20, opacity: 0.6 }}>
              · العدد الأول · رياضة · مجتمع
            </div>
            <h1 style={{
              fontSize: 132, fontWeight: 900, lineHeight: 0.92, letterSpacing: -4,
              margin: 0, color: YH.ink,
            }}>
              يلا، <em style={{ fontStyle: 'italic', color: YH.green, fontWeight: 900 }}>دق</em><br/>
              احجز،<br/>
              العب.
            </h1>
            <div style={{
              marginTop: 36, paddingTop: 28, borderTop: `1px solid ${YH.ink}`,
              display: 'grid', gridTemplateColumns: '120px 1fr', gap: 24,
            }}>
              <div style={{ fontFamily: YH.fonts.mono, fontSize: 11, letterSpacing: 1.5, opacity: 0.6, lineHeight: 1.8 }}>
                المقدّمة<br/>—
              </div>
              <p style={{ fontSize: 17, lineHeight: 1.7, margin: 0 }}>
                {C.heroSub}
              </p>
            </div>
            <div style={{ marginTop: 40, display: 'flex', gap: 16 }}>
              <button style={{
                background: YH.ink, color: YH.white, border: 'none',
                padding: '16px 28px', fontWeight: 700, fontSize: 14,
                fontFamily: YH.fonts.ar, cursor: 'pointer', letterSpacing: 0.5,
              }}>{C.cta1} →</button>
              <button style={{
                background: 'transparent', color: YH.ink, border: `1px solid ${YH.ink}`,
                padding: '15px 28px', fontWeight: 700, fontSize: 14,
                fontFamily: YH.fonts.ar, cursor: 'pointer',
              }}>{C.cta2}</button>
            </div>
          </div>
          <div style={{ position: 'relative' }}>
            <div style={{
              aspectRatio: '3/4', borderRadius: 0,
              background: `linear-gradient(180deg, ${YH.green} 0%, ${YH.greenDeep} 100%)`,
              position: 'relative', overflow: 'hidden',
            }}>
              <YHStripe label="hero — football match" color={YH.white} dark/>
              {/* Issue label */}
              <div style={{
                position: 'absolute', top: 24, right: 24,
                background: YH.white, padding: '8px 14px',
                fontFamily: YH.fonts.mono, fontSize: 11, fontWeight: 700, letterSpacing: 1.5,
              }}>NO. 01 / 2026</div>
              {/* Caption */}
              <div style={{
                position: 'absolute', bottom: 0, left: 0, right: 0,
                padding: '24px', color: YH.white,
                background: 'linear-gradient(0deg, rgba(0,0,0,0.6), transparent)',
              }}>
                <div style={{ fontFamily: YH.fonts.mono, fontSize: 10, letterSpacing: 2, opacity: 0.8, marginBottom: 6 }}>FIG. 01</div>
                <div style={{ fontSize: 14, fontWeight: 600 }}>مباراة عشية في ملعب الفيحاء، المزة، دمشق.</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* STATS — editorial row */}
      <section style={{ padding: '48px 64px', borderBottom: `1px solid ${YH.ink}` }}>
        <div style={{ display: 'grid', gridTemplateColumns: '180px repeat(4, 1fr)', alignItems: 'baseline', gap: 32 }}>
          <div style={{ fontFamily: YH.fonts.mono, fontSize: 11, letterSpacing: 1.5, opacity: 0.6 }}>· الأرقام / 2026</div>
          {C.stats.map((s, i) => (
            <div key={i}>
              <div style={{ fontSize: 64, fontWeight: 900, letterSpacing: -2, lineHeight: 1 }}>{s.num}</div>
              <div style={{ fontSize: 13, fontFamily: YH.fonts.mono, opacity: 0.6, marginTop: 8, letterSpacing: 1 }}>— {s.label}</div>
            </div>
          ))}
        </div>
      </section>

      {/* FEATURE — long form */}
      <section style={{ padding: '100px 64px', borderBottom: `1px solid ${YH.ink}` }}>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 2fr', gap: 64 }}>
          <div>
            <div style={{ fontFamily: YH.fonts.mono, fontSize: 11, letterSpacing: 2, opacity: 0.6, marginBottom: 16 }}>· المميزات</div>
            <div style={{ position: 'sticky', top: 32 }}>
              <h2 style={{ fontSize: 56, fontWeight: 900, lineHeight: 0.95, letterSpacing: -2, margin: 0 }}>
                <em style={{ fontStyle: 'italic', color: YH.green }}>كل شي</em><br/>
                بحاجتو<br/>
                لتلعب.
              </h2>
              <p style={{ fontSize: 15, opacity: 0.7, marginTop: 24, lineHeight: 1.7 }}>
                ست مميزات أساسية صُمّمت لتجربة لعب أبسط، أسرع، وأكثر متعة.
              </p>
            </div>
          </div>
          <div>
            {C.features.map((f, i) => (
              <div key={i} style={{
                paddingTop: 32, paddingBottom: 32,
                borderTop: i === 0 ? `1px solid ${YH.ink}` : 'none',
                borderBottom: `1px solid ${YH.ink}`,
                display: 'grid', gridTemplateColumns: '60px 1fr 80px', gap: 24, alignItems: 'baseline',
              }}>
                <div style={{ fontFamily: YH.fonts.mono, fontSize: 14, opacity: 0.5 }}>0{i+1}</div>
                <div>
                  <div style={{ fontSize: 28, fontWeight: 800, marginBottom: 8 }}>{f.title}</div>
                  <div style={{ fontSize: 15, lineHeight: 1.7, opacity: 0.75, maxWidth: 480 }}>{f.desc}</div>
                </div>
                <div style={{ fontSize: 32, textAlign: 'left' }}>{f.icon}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* PHOTO ESSAY — gallery */}
      <section style={{ padding: '100px 64px', borderBottom: `1px solid ${YH.ink}`, background: YH.cream }}>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 2fr', gap: 32, marginBottom: 48 }}>
          <div style={{ fontFamily: YH.fonts.mono, fontSize: 11, letterSpacing: 2, opacity: 0.6 }}>· مقالة مصوّرة / ٢٠٢٦</div>
          <h2 style={{ fontSize: 72, fontWeight: 900, lineHeight: 0.95, letterSpacing: -3, margin: 0 }}>
            ملاعب <em style={{ fontStyle: 'italic', color: YH.green }}>سوريا</em>،<br/>
            من الشمال للساحل.
          </h2>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(12, 1fr)', gap: 16, gridAutoRows: 100 }}>
          <div style={{ gridColumn: 'span 7', gridRow: 'span 4' }}><YHStripe label="ملعب الفيحاء · دمشق" color={YH.green}/></div>
          <div style={{ gridColumn: 'span 5', gridRow: 'span 2' }}><YHStripe label="نادي الكرامة · حمص" color={YH.green}/></div>
          <div style={{ gridColumn: 'span 5', gridRow: 'span 2' }}><YHStripe label="ملعب النورس · اللاذقية" color={YH.green}/></div>
          <div style={{ gridColumn: 'span 4', gridRow: 'span 3' }}><YHStripe label="ملعب الأهلي · حلب" color={YH.green}/></div>
          <div style={{ gridColumn: 'span 4', gridRow: 'span 3' }}><YHStripe label="مدينة حماة الرياضية" color={YH.green}/></div>
          <div style={{ gridColumn: 'span 4', gridRow: 'span 3' }}><YHStripe label="ملعب السلام · طرطوس" color={YH.green}/></div>
        </div>
      </section>

      {/* HOW IT WORKS — numbered */}
      <section style={{ padding: '100px 64px', borderBottom: `1px solid ${YH.ink}` }}>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 64, alignItems: 'flex-start' }}>
          <div>
            <div style={{ fontFamily: YH.fonts.mono, fontSize: 11, letterSpacing: 2, opacity: 0.6, marginBottom: 16 }}>· كيف بيشتغل</div>
            <h2 style={{ fontSize: 88, fontWeight: 900, lineHeight: 0.9, letterSpacing: -3, margin: 0 }}>
              أربع<br/>خطوات.<br/>
              <em style={{ fontStyle: 'italic', color: YH.green }}>ثلاثين</em><br/>ثانية.
            </h2>
          </div>
          <div>
            {C.steps.map((s, i) => (
              <div key={i} style={{
                padding: '32px 0', borderBottom: `1px solid ${YH.ink}`,
              }}>
                <div style={{ display: 'flex', alignItems: 'baseline', gap: 24, marginBottom: 8 }}>
                  <div style={{ fontSize: 96, fontWeight: 900, color: YH.green, letterSpacing: -3, lineHeight: 1 }}>{s.n}</div>
                  <div style={{ fontSize: 32, fontWeight: 800 }}>{s.title}</div>
                </div>
                <div style={{ fontSize: 15, opacity: 0.7, lineHeight: 1.7, paddingRight: 120 }}>{s.desc}</div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* TESTIMONIALS — pull quotes */}
      <section style={{ padding: '100px 64px', borderBottom: `1px solid ${YH.ink}`, background: YH.ink, color: YH.white }}>
        <div style={{ fontFamily: YH.fonts.mono, fontSize: 11, letterSpacing: 2, opacity: 0.6, marginBottom: 32 }}>· شهادات</div>
        {C.testimonials.map((t, i) => (
          <div key={i} style={{
            display: 'grid', gridTemplateColumns: '180px 1fr 200px', gap: 32, alignItems: 'baseline',
            padding: '48px 0', borderTop: i === 0 ? 'none' : `1px solid rgba(255,255,255,0.15)`,
          }}>
            <div>
              <div style={{ fontSize: 18, fontWeight: 800 }}>{t.name}</div>
              <div style={{ fontSize: 12, opacity: 0.6, marginTop: 6 }}>{t.role}</div>
            </div>
            <div style={{ fontSize: 32, fontWeight: 600, lineHeight: 1.3, letterSpacing: -1 }}>
              «{t.text}»
            </div>
            <div style={{ display: 'flex', gap: 4, justifyContent: 'flex-end' }}>
              {[...Array(t.rating)].map((_, j) => <span key={j} style={{ color: YH.greenGlow, fontSize: 16 }}>★</span>)}
            </div>
          </div>
        ))}
      </section>

      {/* PRICING */}
      <section style={{ padding: '100px 64px', borderBottom: `1px solid ${YH.ink}` }}>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 2fr', gap: 64, marginBottom: 56 }}>
          <div style={{ fontFamily: YH.fonts.mono, fontSize: 11, letterSpacing: 2, opacity: 0.6 }}>· الأسعار</div>
          <h2 style={{ fontSize: 72, fontWeight: 900, lineHeight: 0.95, letterSpacing: -3, margin: 0 }}>
            خطط <em style={{ fontStyle: 'italic', color: YH.green }}>بسيطة</em>.<br/>
            بدون مفاجآت.
          </h2>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 0, border: `1px solid ${YH.ink}` }}>
          {C.pricing.map((p, i) => (
            <div key={i} style={{
              padding: 40, borderRight: i < 2 ? `1px solid ${YH.ink}` : 'none',
              background: p.featured ? YH.green : 'transparent',
              color: p.featured ? YH.white : YH.ink,
            }}>
              <div style={{ fontFamily: YH.fonts.mono, fontSize: 12, letterSpacing: 1.5, opacity: 0.7, marginBottom: 24 }}>0{i+1} · {p.name}</div>
              <div style={{ display: 'flex', alignItems: 'baseline', gap: 8, marginBottom: 8 }}>
                <span style={{ fontSize: 56, fontWeight: 900, letterSpacing: -2 }}>{p.price}</span>
                {p.unit && <span style={{ fontSize: 13, opacity: 0.7 }}>{p.unit}</span>}
              </div>
              <div style={{ fontSize: 14, opacity: 0.7, marginBottom: 32, paddingBottom: 24, borderBottom: `1px solid ${p.featured ? 'rgba(255,255,255,0.3)' : YH.ink}` }}>{p.desc}</div>
              {p.features.map((f, j) => (
                <div key={j} style={{ display: 'flex', gap: 12, marginBottom: 14, fontSize: 14 }}>
                  <span style={{ opacity: 0.5, fontFamily: YH.fonts.mono, fontSize: 11 }}>0{j+1}</span>{f}
                </div>
              ))}
            </div>
          ))}
        </div>
      </section>

      {/* FAQ */}
      <section style={{ padding: '100px 64px', borderBottom: `1px solid ${YH.ink}` }}>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 2fr', gap: 64 }}>
          <div>
            <div style={{ fontFamily: YH.fonts.mono, fontSize: 11, letterSpacing: 2, opacity: 0.6, marginBottom: 16 }}>· الأسئلة المتكررة</div>
            <h2 style={{ fontSize: 72, fontWeight: 900, lineHeight: 0.95, letterSpacing: -3, margin: 0 }}>
              اسأل،<br/><em style={{ fontStyle: 'italic', color: YH.green }}>منجاوب</em>.
            </h2>
          </div>
          <div>
            {C.faq.map((f, i) => (
              <details key={i} style={{
                borderTop: `1px solid ${YH.ink}`, borderBottom: i === C.faq.length - 1 ? `1px solid ${YH.ink}` : 'none',
                padding: '28px 0', cursor: 'pointer',
              }}>
                <summary style={{ fontWeight: 700, fontSize: 22, listStyle: 'none', display: 'flex', justifyContent: 'space-between' }}>
                  {f.q} <span style={{ fontSize: 14, fontFamily: YH.fonts.mono, opacity: 0.5 }}>0{i+1}</span>
                </summary>
                <div style={{ fontSize: 15, opacity: 0.75, lineHeight: 1.7, marginTop: 12, maxWidth: 640 }}>{f.a}</div>
              </details>
            ))}
          </div>
        </div>
      </section>

      {/* CTA — final */}
      <section style={{ padding: '120px 64px', textAlign: 'center', background: YH.cream }}>
        <div style={{ fontFamily: YH.fonts.mono, fontSize: 11, letterSpacing: 2, opacity: 0.6, marginBottom: 24 }}>· COLOPHON · CALL TO PLAY</div>
        <h2 style={{ fontSize: 156, fontWeight: 900, lineHeight: 0.9, letterSpacing: -5, margin: 0 }}>
          يلا.<br/>
          <em style={{ fontStyle: 'italic', color: YH.green }}>حجيز</em>.
        </h2>
        <p style={{ fontSize: 18, opacity: 0.7, marginTop: 32, maxWidth: 540, marginInline: 'auto' }}>حمّل التطبيق وابدا حجزك الأول الآن.</p>
        <div style={{ display: 'flex', gap: 16, justifyContent: 'center', marginTop: 40 }}>
          <button style={{
            background: YH.ink, color: YH.white, border: 'none', padding: '18px 32px',
            fontWeight: 700, fontSize: 14, fontFamily: YH.fonts.ar, cursor: 'pointer',
          }}>App Store →</button>
          <button style={{
            background: YH.green, color: YH.white, border: 'none', padding: '18px 32px',
            fontWeight: 700, fontSize: 14, fontFamily: YH.fonts.ar, cursor: 'pointer',
          }}>Google Play →</button>
        </div>
      </section>

      {/* FOOTER */}
      <footer style={{ padding: '48px 64px', borderTop: `1px solid ${YH.ink}` }}>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr 1fr', gap: 32, marginBottom: 32 }}>
          <YHLogoLockup size={0.7}/>
          <div>
            <div style={{ fontFamily: YH.fonts.mono, fontSize: 11, opacity: 0.5, letterSpacing: 1.5, marginBottom: 14 }}>· المنصة</div>
            {['الرئيسية','المميزات','الأسعار','البطولات'].map((l,i) => <div key={i} style={{ fontSize: 13, marginBottom: 8 }}>{l}</div>)}
          </div>
          <div>
            <div style={{ fontFamily: YH.fonts.mono, fontSize: 11, opacity: 0.5, letterSpacing: 1.5, marginBottom: 14 }}>· الشركة</div>
            {['من نحن','الوظائف','الأخبار','تواصل'].map((l,i) => <div key={i} style={{ fontSize: 13, marginBottom: 8 }}>{l}</div>)}
          </div>
          <div>
            <div style={{ fontFamily: YH.fonts.mono, fontSize: 11, opacity: 0.5, letterSpacing: 1.5, marginBottom: 14 }}>· القانوني</div>
            {['الشروط','الخصوصية','الاسترداد','الكوكيز'].map((l,i) => <div key={i} style={{ fontSize: 13, marginBottom: 8 }}>{l}</div>)}
          </div>
        </div>
        <div style={{ borderTop: `1px solid ${YH.ink}`, paddingTop: 20, display: 'flex', justifyContent: 'space-between', fontSize: 12, fontFamily: YH.fonts.mono, opacity: 0.6, letterSpacing: 1 }}>
          <span>{C.footer.copyright}</span>
          <span>ISSUE 01 · DAMASCUS · 2026</span>
        </div>
      </footer>
    </div>
  );
}

Object.assign(window, { V2Editorial });
