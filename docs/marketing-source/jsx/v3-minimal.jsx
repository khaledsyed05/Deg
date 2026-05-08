// Variation 3 — MINIMAL SPORT
// Clean white space, refined typography, subtle green accents, modern sport feel

function V3Minimal() {
  const C = YH_COPY.ar;
  return (
    <div style={{
      width: '100%', minHeight: 2400, background: YH.white, direction: 'rtl',
      fontFamily: YH.fonts.ar, color: YH.ink, overflow: 'hidden',
    }}>
      {/* NAV */}
      <nav style={{
        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
        padding: '24px 56px', position: 'sticky', top: 0, background: 'rgba(255,255,255,0.85)',
        backdropFilter: 'blur(10px)', zIndex: 10,
      }}>
        <YHLogoLockup size={0.8}/>
        <div style={{
          display: 'flex', gap: 4, alignItems: 'center',
          background: '#F5F5F4', padding: 4, borderRadius: 999,
        }}>
          {C.nav.map((n, i) => (
            <a key={i} style={{
              fontSize: 13, fontWeight: 600, color: YH.ink,
              padding: '10px 18px', borderRadius: 999,
              background: i === 0 ? YH.white : 'transparent',
              boxShadow: i === 0 ? '0 1px 3px rgba(0,0,0,0.08)' : 'none',
            }}>{n}</a>
          ))}
        </div>
        <button style={{
          background: YH.green, color: YH.white, border: 'none',
          padding: '12px 22px', borderRadius: 999, fontWeight: 700, fontSize: 13,
          fontFamily: YH.fonts.ar, cursor: 'pointer',
        }}>{C.download}</button>
      </nav>

      {/* HERO — centered, generous space */}
      <section style={{
        padding: '120px 56px 80px', textAlign: 'center', position: 'relative',
      }}>
        <div style={{
          display: 'inline-flex', gap: 8, alignItems: 'center',
          background: YH.greenSoft, color: YH.greenDeep,
          padding: '8px 16px', borderRadius: 999,
          fontSize: 12, fontWeight: 700, marginBottom: 32,
        }}>
          <span style={{ width: 6, height: 6, borderRadius: 99, background: YH.green }}/>
          {C.heroTag}
        </div>
        <h1 style={{
          fontSize: 96, fontWeight: 800, lineHeight: 1, letterSpacing: -3,
          margin: 0, maxWidth: 900, marginInline: 'auto',
        }}>
          احجز ملعبك المفضّل<br/>
          <span style={{ color: YH.green }}>بضغطة وحدة.</span>
        </h1>
        <p style={{
          fontSize: 19, lineHeight: 1.7, color: YH.ink, opacity: 0.65,
          marginTop: 28, maxWidth: 620, marginInline: 'auto',
        }}>{C.heroSub}</p>
        <div style={{ display: 'flex', gap: 12, justifyContent: 'center', marginTop: 40 }}>
          <button style={{
            background: YH.ink, color: YH.white, border: 'none',
            padding: '16px 28px', borderRadius: 999, fontWeight: 700, fontSize: 15,
            fontFamily: YH.fonts.ar, cursor: 'pointer',
          }}>{C.cta1} ↓</button>
          <button style={{
            background: 'transparent', color: YH.ink, border: '1px solid #D6D3D1',
            padding: '15px 28px', borderRadius: 999, fontWeight: 600, fontSize: 15,
            fontFamily: YH.fonts.ar, cursor: 'pointer',
          }}>{C.cta2}</button>
        </div>

        {/* Phone mock */}
        <div style={{ marginTop: 80, display: 'flex', justifyContent: 'center', position: 'relative' }}>
          <div style={{
            position: 'absolute', width: '70%', height: 320, top: 40, left: '50%',
            transform: 'translateX(-50%)',
            background: 'radial-gradient(ellipse, rgba(11,168,74,0.18), transparent 70%)',
          }}/>
          <div style={{ position: 'relative' }}>
            <YHPhoneMock width={300}>
              <PhoneHomeContent/>
            </YHPhoneMock>
          </div>
        </div>
      </section>

      {/* LOGOS BAR */}
      <section style={{ padding: '48px 56px', borderTop: '1px solid #E7E5E4', borderBottom: '1px solid #E7E5E4' }}>
        <div style={{ textAlign: 'center', fontSize: 12, fontWeight: 600, opacity: 0.5, marginBottom: 24, letterSpacing: 1.5, textTransform: 'uppercase', fontFamily: YH.fonts.mono }}>
          مستخدَم في أكبر النوادي والملاعب السورية
        </div>
        <div style={{ display: 'flex', justifyContent: 'space-around', alignItems: 'center', opacity: 0.45, fontSize: 16, fontWeight: 700, gap: 32 }}>
          <span>الفيحاء</span><span>·</span><span>تشرين</span><span>·</span><span>الجلاء</span><span>·</span><span>الأهلي</span><span>·</span><span>الكرامة</span><span>·</span><span>السلام</span>
        </div>
      </section>

      {/* STATS — clean */}
      <section style={{ padding: '80px 56px' }}>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 24 }}>
          {C.stats.map((s, i) => (
            <div key={i} style={{ textAlign: 'center' }}>
              <div style={{ fontSize: 64, fontWeight: 800, letterSpacing: -2, color: YH.green, lineHeight: 1 }}>{s.num}</div>
              <div style={{ fontSize: 14, opacity: 0.6, marginTop: 8 }}>{s.label}</div>
            </div>
          ))}
        </div>
      </section>

      {/* FEATURES — minimal cards */}
      <section style={{ padding: '100px 56px', background: '#FAFAF9' }}>
        <div style={{ textAlign: 'center', maxWidth: 640, marginInline: 'auto', marginBottom: 64 }}>
          <div style={{ fontSize: 13, fontWeight: 700, color: YH.green, letterSpacing: 2, marginBottom: 16, textTransform: 'uppercase' }}>المميزات</div>
          <h2 style={{ fontSize: 56, fontWeight: 800, letterSpacing: -2, margin: 0, lineHeight: 1.05 }}>كل شي بحاجتو لتلعب.</h2>
          <p style={{ fontSize: 17, opacity: 0.65, marginTop: 16, lineHeight: 1.6 }}>من البحث للحجز للدفع — تجربة سلسة من البداية للنهاية.</p>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 16 }}>
          {C.features.map((f, i) => (
            <div key={i} style={{
              padding: 32, background: YH.white, borderRadius: 20,
              border: '1px solid #E7E5E4',
            }}>
              <div style={{
                width: 48, height: 48, background: YH.greenSoft, borderRadius: 12,
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: 22, marginBottom: 24,
              }}>{f.icon}</div>
              <div style={{ fontSize: 19, fontWeight: 700, marginBottom: 8 }}>{f.title}</div>
              <div style={{ fontSize: 14, lineHeight: 1.65, opacity: 0.65 }}>{f.desc}</div>
            </div>
          ))}
        </div>
      </section>

      {/* HOW IT WORKS — horizontal flow */}
      <section style={{ padding: '100px 56px' }}>
        <div style={{ textAlign: 'center', maxWidth: 640, marginInline: 'auto', marginBottom: 64 }}>
          <div style={{ fontSize: 13, fontWeight: 700, color: YH.green, letterSpacing: 2, marginBottom: 16, textTransform: 'uppercase' }}>كيف بيشتغل</div>
          <h2 style={{ fontSize: 56, fontWeight: 800, letterSpacing: -2, margin: 0, lineHeight: 1.05 }}>أربع خطوات. ثلاثين ثانية.</h2>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 0, position: 'relative' }}>
          {/* connector line */}
          <div style={{ position: 'absolute', top: 32, left: '12.5%', right: '12.5%', height: 1, borderTop: `2px dashed ${YH.green}`, opacity: 0.3 }}/>
          {C.steps.map((s, i) => (
            <div key={i} style={{ textAlign: 'center', padding: '0 16px', position: 'relative' }}>
              <div style={{
                width: 64, height: 64, borderRadius: 999, background: YH.white,
                border: `2px solid ${YH.green}`, color: YH.green,
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontWeight: 800, fontSize: 22, marginInline: 'auto', marginBottom: 24,
                position: 'relative', zIndex: 1,
              }}>{s.n}</div>
              <div style={{ fontSize: 19, fontWeight: 700, marginBottom: 8 }}>{s.title}</div>
              <div style={{ fontSize: 14, opacity: 0.65, lineHeight: 1.6 }}>{s.desc}</div>
            </div>
          ))}
        </div>
      </section>

      {/* SHOWCASE BIG */}
      <section style={{ padding: '80px 56px' }}>
        <div style={{
          background: `linear-gradient(135deg, ${YH.green}, ${YH.greenDeep})`,
          borderRadius: 32, padding: '80px 64px', display: 'grid', gridTemplateColumns: '1fr 1fr',
          gap: 48, alignItems: 'center', position: 'relative', overflow: 'hidden',
          color: YH.white,
        }}>
          {/* dot pattern */}
          <div style={{
            position: 'absolute', inset: 0,
            backgroundImage: `radial-gradient(circle, rgba(255,255,255,0.15) 1.5px, transparent 1.5px)`,
            backgroundSize: '28px 28px',
          }}/>
          <div style={{ position: 'relative' }}>
            <div style={{ fontSize: 12, fontWeight: 700, letterSpacing: 2, opacity: 0.85, marginBottom: 20, textTransform: 'uppercase' }}>للأندية والملاعب</div>
            <h2 style={{ fontSize: 56, fontWeight: 800, letterSpacing: -2, margin: 0, lineHeight: 1.05 }}>
              عندك ملعب؟<br/>زيد إيراداتك ٤٠٪.
            </h2>
            <p style={{ fontSize: 17, opacity: 0.95, marginTop: 20, lineHeight: 1.65 }}>
              لوحة تحكم ذكية، تقويم تلقائي، ودفع آمن — اشتغل أقل واربح أكتر.
            </p>
            <button style={{
              marginTop: 32, background: YH.white, color: YH.greenDeep, border: 'none',
              padding: '16px 28px', borderRadius: 999, fontWeight: 700, fontSize: 15,
              fontFamily: YH.fonts.ar, cursor: 'pointer',
            }}>سجّل ملعبك معنا →</button>
          </div>
          <div style={{ position: 'relative', display: 'flex', justifyContent: 'center' }}>
            <div style={{
              width: '100%', maxWidth: 380, background: 'rgba(255,255,255,0.12)',
              borderRadius: 20, padding: 24, backdropFilter: 'blur(8px)', border: '1px solid rgba(255,255,255,0.2)',
            }}>
              <div style={{ fontSize: 12, opacity: 0.8, marginBottom: 4 }}>إيرادات هذا الأسبوع</div>
              <div style={{ fontSize: 36, fontWeight: 800, letterSpacing: -1 }}>٢,٤٥٠,٠٠٠ ل.س</div>
              <div style={{ display: 'flex', gap: 6, alignItems: 'center', fontSize: 13, marginTop: 4, opacity: 0.9 }}>
                <span style={{ color: '#7FFFB6' }}>↑ ٤٠٪</span> مقارنة بالشهر الماضي
              </div>
              <div style={{ marginTop: 24, display: 'flex', alignItems: 'flex-end', gap: 6, height: 80 }}>
                {[40, 65, 50, 80, 70, 95, 88].map((h, i) => (
                  <div key={i} style={{ flex: 1, height: `${h}%`, background: '#7FFFB6', borderRadius: 4 }}/>
                ))}
              </div>
              <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 8, fontSize: 11, opacity: 0.7 }}>
                <span>س</span><span>أ</span><span>ث</span><span>أر</span><span>خ</span><span>ج</span><span>س</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* TESTIMONIALS — minimal */}
      <section style={{ padding: '100px 56px', background: '#FAFAF9' }}>
        <div style={{ textAlign: 'center', maxWidth: 640, marginInline: 'auto', marginBottom: 56 }}>
          <div style={{ fontSize: 13, fontWeight: 700, color: YH.green, letterSpacing: 2, marginBottom: 16, textTransform: 'uppercase' }}>آراء العملاء</div>
          <h2 style={{ fontSize: 56, fontWeight: 800, letterSpacing: -2, margin: 0, lineHeight: 1.05 }}>عم يحبّوه. منيح.</h2>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 20 }}>
          {C.testimonials.map((t, i) => (
            <div key={i} style={{
              padding: 32, background: YH.white, borderRadius: 20, border: '1px solid #E7E5E4',
              display: 'flex', flexDirection: 'column', gap: 20,
            }}>
              <div style={{ display: 'flex', gap: 4 }}>
                {[...Array(t.rating)].map((_, j) => <span key={j} style={{ color: YH.amber, fontSize: 16 }}>★</span>)}
              </div>
              <div style={{ fontSize: 16, lineHeight: 1.6 }}>«{t.text}»</div>
              <div style={{ display: 'flex', gap: 12, alignItems: 'center', marginTop: 'auto', paddingTop: 20, borderTop: '1px solid #F5F5F4' }}>
                <div style={{ width: 44, height: 44, borderRadius: 999, background: YH.greenSoft, color: YH.greenDeep, display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 800 }}>{t.name[0]}</div>
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
      <section style={{ padding: '100px 56px' }}>
        <div style={{ textAlign: 'center', maxWidth: 640, marginInline: 'auto', marginBottom: 56 }}>
          <div style={{ fontSize: 13, fontWeight: 700, color: YH.green, letterSpacing: 2, marginBottom: 16, textTransform: 'uppercase' }}>الأسعار</div>
          <h2 style={{ fontSize: 56, fontWeight: 800, letterSpacing: -2, margin: 0, lineHeight: 1.05 }}>خطط بسيطة. بدون مفاجآت.</h2>
        </div>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 20, maxWidth: 1100, marginInline: 'auto' }}>
          {C.pricing.map((p, i) => (
            <div key={i} style={{
              padding: 36, borderRadius: 24,
              background: p.featured ? YH.ink : YH.white,
              color: p.featured ? YH.white : YH.ink,
              border: p.featured ? 'none' : '1px solid #E7E5E4',
              position: 'relative',
            }}>
              {p.featured && (
                <div style={{
                  position: 'absolute', top: 24, left: 24,
                  background: YH.greenGlow, color: YH.ink,
                  padding: '4px 10px', borderRadius: 99, fontSize: 11, fontWeight: 700,
                }}>الأكثر شعبية</div>
              )}
              <div style={{ fontSize: 14, fontWeight: 700, opacity: 0.7, marginBottom: 12 }}>{p.name}</div>
              <div style={{ display: 'flex', alignItems: 'baseline', gap: 6, marginBottom: 8 }}>
                <span style={{ fontSize: 44, fontWeight: 800, letterSpacing: -1 }}>{p.price}</span>
                {p.unit && <span style={{ fontSize: 13, opacity: 0.6 }}>{p.unit}</span>}
              </div>
              <div style={{ fontSize: 14, opacity: 0.7, marginBottom: 28, paddingBottom: 24, borderBottom: `1px solid ${p.featured ? 'rgba(255,255,255,0.15)' : '#F5F5F4'}` }}>{p.desc}</div>
              {p.features.map((f, j) => (
                <div key={j} style={{ display: 'flex', gap: 10, alignItems: 'center', marginBottom: 12, fontSize: 14 }}>
                  <span style={{ color: p.featured ? YH.greenGlow : YH.green, fontWeight: 800 }}>✓</span> {f}
                </div>
              ))}
              <button style={{
                width: '100%', marginTop: 24, padding: '14px 20px', borderRadius: 999,
                background: p.featured ? YH.greenGlow : YH.ink, color: p.featured ? YH.ink : YH.white,
                border: 'none', fontWeight: 700, fontSize: 14, fontFamily: YH.fonts.ar, cursor: 'pointer',
              }}>ابدأ الآن</button>
            </div>
          ))}
        </div>
      </section>

      {/* FAQ */}
      <section style={{ padding: '100px 56px', background: '#FAFAF9' }}>
        <div style={{ maxWidth: 800, marginInline: 'auto' }}>
          <div style={{ textAlign: 'center', marginBottom: 56 }}>
            <div style={{ fontSize: 13, fontWeight: 700, color: YH.green, letterSpacing: 2, marginBottom: 16, textTransform: 'uppercase' }}>أسئلة متكررة</div>
            <h2 style={{ fontSize: 56, fontWeight: 800, letterSpacing: -2, margin: 0, lineHeight: 1.05 }}>اسأل، منجاوب.</h2>
          </div>
          {C.faq.map((f, i) => (
            <details key={i} style={{
              background: YH.white, padding: '20px 28px', borderRadius: 16,
              border: '1px solid #E7E5E4', marginBottom: 12, cursor: 'pointer',
            }}>
              <summary style={{ fontWeight: 700, fontSize: 16, listStyle: 'none', display: 'flex', justifyContent: 'space-between' }}>
                {f.q} <span style={{ color: YH.green, fontSize: 20 }}>+</span>
              </summary>
              <div style={{ fontSize: 14, opacity: 0.7, lineHeight: 1.7, marginTop: 12 }}>{f.a}</div>
            </details>
          ))}
        </div>
      </section>

      {/* FINAL CTA */}
      <section style={{ padding: '120px 56px', textAlign: 'center' }}>
        <h2 style={{ fontSize: 88, fontWeight: 800, letterSpacing: -3, lineHeight: 1, margin: 0 }}>
          يلا، شو عم تستنى؟
        </h2>
        <p style={{ fontSize: 18, opacity: 0.65, marginTop: 20 }}>حمّل التطبيق وابدا حجزك الأول الآن — مجاناً.</p>
        <div style={{ display: 'flex', gap: 12, justifyContent: 'center', marginTop: 36 }}>
          <button style={{
            background: YH.ink, color: YH.white, border: 'none', padding: '16px 28px',
            borderRadius: 999, fontWeight: 700, fontSize: 15, fontFamily: YH.fonts.ar, cursor: 'pointer',
          }}>App Store ↓</button>
          <button style={{
            background: YH.green, color: YH.white, border: 'none', padding: '16px 28px',
            borderRadius: 999, fontWeight: 700, fontSize: 15, fontFamily: YH.fonts.ar, cursor: 'pointer',
          }}>Google Play ↓</button>
        </div>
      </section>

      {/* FOOTER */}
      <footer style={{ padding: '48px 56px', borderTop: '1px solid #E7E5E4' }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 24 }}>
          <YHLogoLockup size={0.7}/>
          <div style={{ display: 'flex', gap: 24, fontSize: 13, opacity: 0.7 }}>
            {['الشروط','الخصوصية','تواصل','مساعدة'].map((l,i) => <a key={i}>{l}</a>)}
          </div>
          <div style={{ fontSize: 13, opacity: 0.5 }}>{C.footer.copyright}</div>
        </div>
      </footer>
    </div>
  );
}

Object.assign(window, { V3Minimal });
