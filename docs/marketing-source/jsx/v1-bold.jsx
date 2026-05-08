// Variation 1 — BOLD ENERGETIC
// Big neon-green hero, oversized type, ticker, bold blocks, playful energy

function V1Bold() {
  const C = YH_COPY.ar;
  return (
    <div style={{
      width: '100%', minHeight: 2400, background: YH.cream, direction: 'rtl',
      fontFamily: YH.fonts.ar, color: YH.ink, overflow: 'hidden', position: 'relative',
    }}>
      {/* NAV */}
      <nav style={{
        display: 'flex', alignItems: 'center', justifyContent: 'space-between',
        padding: '24px 48px', borderBottom: `2px solid ${YH.ink}`, background: YH.cream,
        position: 'sticky', top: 0, zIndex: 10,
      }}>
        <YHLogoLockup size={0.85}/>
        <div style={{ display: 'flex', gap: 32, alignItems: 'center' }}>
          {C.nav.map((n, i) => (
            <a key={i} style={{ fontWeight: 600, fontSize: 14, color: YH.ink, textDecoration: 'none' }}>{n}</a>
          ))}
        </div>
        <button style={{
          background: YH.ink, color: YH.white, border: 'none',
          padding: '14px 28px', borderRadius: 999, fontWeight: 700, fontSize: 14,
          fontFamily: YH.fonts.ar, cursor: 'pointer',
        }}>{C.download} ↓</button>
      </nav>

      {/* HERO — split: text left, phone with green panel */}
      <section style={{
        display: 'grid', gridTemplateColumns: '1.2fr 1fr', gap: 0, minHeight: 720,
        position: 'relative',
      }}>
        <div style={{ padding: '80px 64px 64px', display: 'flex', flexDirection: 'column', justifyContent: 'center' }}>
          <div style={{
            display: 'inline-flex', alignSelf: 'flex-start', gap: 8, alignItems: 'center',
            background: YH.ink, color: YH.greenGlow, padding: '8px 16px', borderRadius: 999,
            fontSize: 12, fontWeight: 700, fontFamily: YH.fonts.mono, letterSpacing: 1,
            textTransform: 'uppercase', marginBottom: 28,
          }}>
            <span style={{ width: 6, height: 6, borderRadius: 99, background: YH.greenGlow }}/>
            {C.heroTag}
          </div>
          <h1 style={{
            fontSize: 104, fontWeight: 900, lineHeight: 0.95, letterSpacing: -3,
            margin: 0, color: YH.ink,
          }}>
            {C.heroH1[0]}<br/>
            <span style={{
              background: YH.green, color: YH.white,
              padding: '0 18px', borderRadius: 12, display: 'inline-block', marginTop: 12,
            }}>{C.heroH1[1]}</span>
          </h1>
          <p style={{
            fontSize: 19, lineHeight: 1.6, color: YH.ink, opacity: 0.75,
            marginTop: 32, maxWidth: 540,
          }}>{C.heroSub}</p>
          <div style={{ display: 'flex', gap: 16, marginTop: 40 }}>
            <button style={{
              background: YH.ink, color: YH.greenGlow, border: 'none',
              padding: '20px 36px', borderRadius: 999, fontWeight: 800, fontSize: 16,
              fontFamily: YH.fonts.ar, cursor: 'pointer', display: 'inline-flex', gap: 10, alignItems: 'center',
            }}>{C.cta1} <span style={{ fontSize: 18 }}>↓</span></button>
            <button style={{
              background: 'transparent', color: YH.ink, border: `2px solid ${YH.ink}`,
              padding: '18px 32px', borderRadius: 999, fontWeight: 700, fontSize: 16,
              fontFamily: YH.fonts.ar, cursor: 'pointer',
            }}>{C.cta2} →</button>
          </div>
          {/* avatars + stat */}
          <div style={{ display: 'flex', alignItems: 'center', gap: 16, marginTop: 48 }}>
            <div style={{ display: 'flex' }}>
              {['#0BA84A','#FFB800','#FF6B5B','#0A1A2F'].map((c, i) => (
                <div key={i} style={{
                  width: 40, height: 40, borderRadius: 999, background: c,
                  border: `3px solid ${YH.cream}`, marginLeft: i > 0 ? -12 : 0,
                  display: 'flex', alignItems: 'center', justifyContent: 'center',
                  color: '#fff', fontWeight: 800, fontSize: 14,
                }}>{['ف','ل','ر','ك'][i]}</div>
              ))}
            </div>
            <div>
              <div style={{ fontWeight: 800, fontSize: 16 }}>+50,000 لاعب</div>
              <div style={{ fontSize: 13, opacity: 0.6 }}>عم يحجزو معنا كل أسبوع</div>
            </div>
          </div>
        </div>

        {/* Phone panel */}
        <div style={{
          background: YH.green, position: 'relative', display: 'flex', alignItems: 'center', justifyContent: 'center',
          overflow: 'hidden',
        }}>
          {/* dot pattern */}
          <div style={{
            position: 'absolute', inset: 0,
            backgroundImage: `radial-gradient(circle, rgba(255,255,255,0.25) 1.5px, transparent 1.5px)`,
            backgroundSize: '24px 24px', opacity: 0.5,
          }}/>
          {/* big circles */}
          <div style={{ position: 'absolute', top: -120, right: -120, width: 360, height: 360,
            borderRadius: 999, border: `2px solid rgba(255,255,255,0.2)` }}/>
          <div style={{ position: 'absolute', bottom: -80, left: -80, width: 260, height: 260,
            borderRadius: 999, background: YH.greenDeep, opacity: 0.3 }}/>

          <div style={{ position: 'relative', transform: 'rotate(-4deg)' }}>
            <YHPhoneMock width={300}>
              <PhoneHomeContent/>
            </YHPhoneMock>
            {/* floating tag */}
            <div style={{
              position: 'absolute', top: -20, left: -40,
              background: YH.amber, color: YH.ink, padding: '12px 18px',
              borderRadius: 14, fontWeight: 800, fontSize: 14, transform: 'rotate(-8deg)',
              boxShadow: '0 8px 20px rgba(0,0,0,0.2)',
            }}>⚡ حجز فوري</div>
            <div style={{
              position: 'absolute', bottom: 80, right: -52,
              background: YH.white, color: YH.ink, padding: '12px 16px',
              borderRadius: 14, fontWeight: 700, fontSize: 13, transform: 'rotate(6deg)',
              boxShadow: '0 8px 20px rgba(0,0,0,0.2)', display: 'flex', alignItems: 'center', gap: 8,
            }}>⭐ ٤.٩ تقييم</div>
          </div>
        </div>
      </section>

      {/* TICKER */}
      <div style={{
        background: YH.ink, color: YH.greenGlow, padding: '20px 0', overflow: 'hidden',
        borderTop: `2px solid ${YH.ink}`, borderBottom: `2px solid ${YH.ink}`,
      }}>
        <div style={{
          display: 'flex', gap: 48, whiteSpace: 'nowrap',
          fontSize: 22, fontWeight: 800, letterSpacing: -0.5,
        }}>
          {[...Array(3)].map((_, i) => (
            <div key={i} style={{ display: 'flex', gap: 48 }}>
              <span>★ كرة قدم</span><span>★ تنس</span><span>★ سلة</span>
              <span>★ طائرة</span><span>★ بادل</span><span>★ سكواش</span>
              <span>★ بليارد</span><span>★ بولينغ</span>
            </div>
          ))}
        </div>
      </div>

      {/* STATS BIG */}
      <section style={{ padding: '80px 64px', borderBottom: `2px solid ${YH.ink}` }}>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 24 }}>
          {C.stats.map((s, i) => (
            <div key={i} style={{
              padding: '32px 24px', background: i === 1 ? YH.green : YH.white,
              border: `2px solid ${YH.ink}`, borderRadius: 20,
              boxShadow: `6px 6px 0 ${YH.ink}`,
            }}>
              <div style={{ fontSize: 72, fontWeight: 900, lineHeight: 1, color: i === 1 ? YH.white : YH.ink, letterSpacing: -2 }}>{s.num}</div>
              <div style={{ fontSize: 16, fontWeight: 600, marginTop: 8, color: i === 1 ? YH.white : YH.ink, opacity: i === 1 ? 0.95 : 0.7 }}>{s.label}</div>
            </div>
          ))}
        </div>
      </section>

      {/* FEATURES — bento */}
      <section style={{ padding: '100px 64px', background: YH.white, borderBottom: `2px solid ${YH.ink}` }}>
        <SectionHead kicker="// المميزات" title="كل شي بحاجتو لتلعب" sub="من البحث للحجز للدفع — يلا حجيز عم يخلّيها أسهل من أي وقت."/>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 20, marginTop: 48 }}>
          {C.features.map((f, i) => (
            <div key={i} style={{
              padding: 32, border: `2px solid ${YH.ink}`, borderRadius: 20, background: YH.cream,
              boxShadow: `4px 4px 0 ${YH.ink}`,
              gridColumn: i === 0 ? 'span 2' : 'span 1',
              minHeight: i === 0 ? 280 : 220,
              display: 'flex', flexDirection: 'column',
              ...(i === 0 ? { background: YH.green, color: YH.white } : {}),
            }}>
              <div style={{
                width: 56, height: 56, background: i === 0 ? YH.white : YH.ink,
                color: i === 0 ? YH.ink : YH.greenGlow,
                borderRadius: 14, display: 'flex', alignItems: 'center', justifyContent: 'center',
                fontSize: 28, marginBottom: 24,
              }}>{f.icon}</div>
              <div style={{ fontSize: 24, fontWeight: 800, marginBottom: 10, color: i === 0 ? YH.white : YH.ink }}>{f.title}</div>
              <div style={{ fontSize: 15, lineHeight: 1.6, opacity: i === 0 ? 0.9 : 0.7, color: i === 0 ? YH.white : YH.ink }}>{f.desc}</div>
            </div>
          ))}
        </div>
      </section>

      {/* HOW IT WORKS */}
      <section style={{ padding: '100px 64px', background: YH.ink, color: YH.white }}>
        <SectionHead kicker="// كيف بيشتغل" title="٤ خطوات. ثلاثين ثانية." dark/>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 20, marginTop: 48 }}>
          {C.steps.map((s, i) => (
            <div key={i} style={{
              padding: 28, border: `2px solid ${YH.greenGlow}`, borderRadius: 18,
              background: 'rgba(34,210,106,0.05)',
            }}>
              <div style={{ fontFamily: YH.fonts.mono, fontSize: 14, color: YH.greenGlow, letterSpacing: 2, marginBottom: 24 }}>{s.n}</div>
              <div style={{ fontSize: 22, fontWeight: 800, marginBottom: 10 }}>{s.title}</div>
              <div style={{ fontSize: 14, lineHeight: 1.6, opacity: 0.7 }}>{s.desc}</div>
            </div>
          ))}
        </div>
      </section>

      {/* PRICING */}
      <section style={{ padding: '100px 64px', background: YH.cream, borderTop: `2px solid ${YH.ink}` }}>
        <SectionHead kicker="// الأسعار" title="خطط بسيطة. بدون مفاجآت." sub="ابدأ مجاناً وارقّي وقت ما تجاهز."/>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 20, marginTop: 48 }}>
          {C.pricing.map((p, i) => (
            <div key={i} style={{
              padding: 36, borderRadius: 24, border: `2px solid ${YH.ink}`,
              background: p.featured ? YH.green : YH.white,
              color: p.featured ? YH.white : YH.ink,
              boxShadow: p.featured ? `8px 8px 0 ${YH.ink}` : `4px 4px 0 ${YH.ink}`,
              transform: p.featured ? 'scale(1.04)' : 'none',
            }}>
              <div style={{ fontSize: 14, fontWeight: 700, opacity: 0.7, marginBottom: 8, fontFamily: YH.fonts.mono, letterSpacing: 1.5, textTransform: 'uppercase' }}>{p.name}</div>
              <div style={{ display: 'flex', alignItems: 'baseline', gap: 8, marginBottom: 8 }}>
                <span style={{ fontSize: 56, fontWeight: 900, letterSpacing: -2 }}>{p.price}</span>
                {p.unit && <span style={{ fontSize: 14, opacity: 0.7 }}>{p.unit}</span>}
              </div>
              <div style={{ fontSize: 14, opacity: 0.7, marginBottom: 28 }}>{p.desc}</div>
              <div style={{ height: 1, background: p.featured ? 'rgba(255,255,255,0.3)' : YH.line, marginBottom: 24 }}/>
              {p.features.map((f, j) => (
                <div key={j} style={{ display: 'flex', gap: 10, alignItems: 'center', marginBottom: 12, fontSize: 14, fontWeight: 500 }}>
                  <span style={{ color: p.featured ? YH.white : YH.green, fontWeight: 900 }}>✓</span> {f}
                </div>
              ))}
              <button style={{
                width: '100%', marginTop: 20, padding: '16px 24px', borderRadius: 12,
                background: p.featured ? YH.ink : YH.green, color: p.featured ? YH.greenGlow : YH.white,
                border: 'none', fontWeight: 800, fontSize: 15, fontFamily: YH.fonts.ar, cursor: 'pointer',
              }}>ابدا الآن</button>
            </div>
          ))}
        </div>
      </section>

      {/* TESTIMONIALS */}
      <section style={{ padding: '100px 64px', background: YH.white, borderTop: `2px solid ${YH.ink}` }}>
        <SectionHead kicker="// آراء العملاء" title="عم يحبّوه. منيح."/>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 20, marginTop: 48 }}>
          {C.testimonials.map((t, i) => (
            <div key={i} style={{
              padding: 32, border: `2px solid ${YH.ink}`, borderRadius: 20, background: YH.cream,
              display: 'flex', flexDirection: 'column', gap: 20,
            }}>
              <div style={{ display: 'flex', gap: 4 }}>
                {[...Array(t.rating)].map((_, j) => <span key={j} style={{ color: YH.amber, fontSize: 18 }}>★</span>)}
              </div>
              <div style={{ fontSize: 17, lineHeight: 1.65, fontWeight: 500 }}>«{t.text}»</div>
              <div style={{ display: 'flex', gap: 12, alignItems: 'center', marginTop: 'auto' }}>
                <div style={{ width: 44, height: 44, borderRadius: 999, background: YH.green, color: YH.white, display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 800 }}>{t.name[0]}</div>
                <div>
                  <div style={{ fontWeight: 800, fontSize: 15 }}>{t.name}</div>
                  <div style={{ fontSize: 12, opacity: 0.6 }}>{t.role}</div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* GALLERY */}
      <section style={{ padding: '100px 64px', background: YH.cream, borderTop: `2px solid ${YH.ink}` }}>
        <SectionHead kicker="// معرض الملاعب" title="أكتر من ٢,٠٠٠ ملعب" sub="من ملاعب الحارة لمدن الرياضة الكبيرة."/>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 16, marginTop: 48, gridAutoRows: 200 }}>
          {[
            { row: 'span 2', label: 'ملعب الفيحاء · دمشق' },
            { label: 'تشرين · برزة' },
            { label: 'الجلاء · المالكي' },
            { label: 'النورس · اللاذقية' },
            { row: 'span 2', label: 'الأهلي · حلب' },
            { label: 'الكرامة · حمص' },
          ].map((g, i) => (
            <div key={i} style={{
              gridRow: g.row, border: `2px solid ${YH.ink}`, borderRadius: 16, overflow: 'hidden',
            }}>
              <YHStripe label={g.label}/>
            </div>
          ))}
        </div>
      </section>

      {/* FAQ */}
      <section style={{ padding: '100px 64px', background: YH.white, borderTop: `2px solid ${YH.ink}` }}>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1.5fr', gap: 64 }}>
          <div>
            <div style={{ fontFamily: YH.fonts.mono, fontSize: 13, color: YH.green, letterSpacing: 2, marginBottom: 16 }}>// أسئلة متكررة</div>
            <h2 style={{ fontSize: 56, fontWeight: 900, lineHeight: 1, letterSpacing: -2, margin: 0 }}>اسأل،<br/>منجاوب.</h2>
            <p style={{ fontSize: 16, opacity: 0.7, marginTop: 20, lineHeight: 1.6 }}>ما لقيت إجابة لسؤالك؟ تواصل معنا مباشرة.</p>
          </div>
          <div>
            {C.faq.map((f, i) => (
              <details key={i} style={{
                borderBottom: `2px solid ${YH.ink}`, padding: '20px 0', cursor: 'pointer',
              }}>
                <summary style={{ fontWeight: 800, fontSize: 18, listStyle: 'none', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  {f.q} <span style={{ fontSize: 24, color: YH.green }}>+</span>
                </summary>
                <div style={{ fontSize: 15, opacity: 0.75, lineHeight: 1.7, marginTop: 12 }}>{f.a}</div>
              </details>
            ))}
          </div>
        </div>
      </section>

      {/* FINAL CTA */}
      <section style={{ padding: '100px 64px', background: YH.green, position: 'relative', overflow: 'hidden' }}>
        <div style={{
          position: 'absolute', inset: 0,
          backgroundImage: `radial-gradient(circle, rgba(255,255,255,0.2) 1.5px, transparent 1.5px)`,
          backgroundSize: '32px 32px',
        }}/>
        <div style={{ position: 'relative', textAlign: 'center', maxWidth: 800, margin: '0 auto', color: YH.white }}>
          <h2 style={{ fontSize: 96, fontWeight: 900, lineHeight: 0.95, letterSpacing: -3, margin: 0 }}>
            يلا، شو<br/>عم تستنى؟
          </h2>
          <p style={{ fontSize: 20, marginTop: 24, opacity: 0.95 }}>حمّل التطبيق وابدا حجزك الأول الآن — مجاناً.</p>
          <div style={{ display: 'flex', gap: 16, justifyContent: 'center', marginTop: 40 }}>
            <button style={{
              background: YH.ink, color: YH.greenGlow, border: 'none', padding: '20px 36px',
              borderRadius: 999, fontWeight: 800, fontSize: 16, fontFamily: YH.fonts.ar, cursor: 'pointer',
            }}>App Store ↓</button>
            <button style={{
              background: YH.white, color: YH.ink, border: 'none', padding: '20px 36px',
              borderRadius: 999, fontWeight: 800, fontSize: 16, fontFamily: YH.fonts.ar, cursor: 'pointer',
            }}>Google Play ↓</button>
          </div>
        </div>
      </section>

      {/* FOOTER */}
      <footer style={{ padding: '64px 64px 40px', background: YH.ink, color: YH.white }}>
        <div style={{ display: 'grid', gridTemplateColumns: '1.5fr 1fr 1fr 1fr', gap: 48, marginBottom: 48 }}>
          <div>
            <YHLogoLockup size={0.85} fg={YH.white} accent={YH.greenGlow} markBg={YH.greenGlow} markFg={YH.ink}/>
            <p style={{ fontSize: 14, opacity: 0.6, lineHeight: 1.7, marginTop: 20, maxWidth: 320 }}>{C.footer.about}</p>
          </div>
          <FooterCol title="المنصة" links={['الرئيسية','المميزات','الأسعار','البطولات']}/>
          <FooterCol title="الشركة" links={['من نحن','الوظائف','الأخبار','تواصل']}/>
          <FooterCol title="القانوني" links={['الشروط','الخصوصية','الاسترداد','الكوكيز']}/>
        </div>
        <div style={{ borderTop: `1px solid rgba(255,255,255,0.15)`, paddingTop: 24, display: 'flex', justifyContent: 'space-between', fontSize: 13, opacity: 0.6 }}>
          <div>{C.footer.copyright}</div>
          <div>صُنع بـ ❤ في دمشق</div>
        </div>
      </footer>
    </div>
  );
}

function SectionHead({ kicker, title, sub, dark }) {
  return (
    <div style={{ maxWidth: 720 }}>
      <div style={{ fontFamily: YH.fonts.mono, fontSize: 13, color: YH.green, letterSpacing: 2, marginBottom: 16, textTransform: 'uppercase' }}>{kicker}</div>
      <h2 style={{ fontSize: 64, fontWeight: 900, lineHeight: 1, letterSpacing: -2, margin: 0, color: dark ? YH.white : YH.ink }}>{title}</h2>
      {sub && <p style={{ fontSize: 18, opacity: dark ? 0.75 : 0.7, marginTop: 18, lineHeight: 1.6, color: dark ? YH.white : YH.ink }}>{sub}</p>}
    </div>
  );
}

function FooterCol({ title, links }) {
  return (
    <div>
      <div style={{ fontFamily: YH.fonts.mono, fontSize: 12, opacity: 0.5, letterSpacing: 2, marginBottom: 18, textTransform: 'uppercase' }}>{title}</div>
      {links.map((l, i) => <div key={i} style={{ fontSize: 14, marginBottom: 12, opacity: 0.85 }}>{l}</div>)}
    </div>
  );
}

// Phone home content (used in hero phone mock)
function PhoneHomeContent() {
  return (
    <div style={{ padding: '52px 16px 16px', height: '100%', fontFamily: YH.fonts.ar, direction: 'rtl', background: '#FAFAF9', fontSize: 11 }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
        <div>
          <div style={{ fontSize: 11, color: '#78716C' }}>أهلاً، كريم</div>
          <div style={{ fontSize: 16, fontWeight: 800 }}>وين رح تلعب اليوم؟</div>
        </div>
        <div style={{ width: 32, height: 32, borderRadius: 999, background: YH.green }}/>
      </div>
      <div style={{ background: '#F5F5F4', borderRadius: 12, padding: '10px 14px', fontSize: 11, color: '#78716C', marginBottom: 18 }}>
        🔍 دوّر عن ملعب…
      </div>
      <div style={{ display: 'flex', gap: 8, marginBottom: 16 }}>
        {['كرة قدم','تنس','سلة','طائرة'].map((s, i) => (
          <div key={i} style={{
            padding: '7px 12px', borderRadius: 99, fontSize: 10, fontWeight: 700,
            background: i === 0 ? YH.green : '#F5F5F4', color: i === 0 ? '#fff' : '#0C0A09', whiteSpace: 'nowrap',
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
          background: '#fff', border: '1px solid #E7E5E4', borderRadius: 12,
          padding: 10, display: 'flex', gap: 10, marginBottom: 8, alignItems: 'center',
        }}>
          <div style={{ width: 48, height: 48, borderRadius: 8, background: YH.greenSoft, flexShrink: 0 }}/>
          <div style={{ flex: 1, minWidth: 0 }}>
            <div style={{ fontWeight: 800, fontSize: 11 }}>{g.n}</div>
            <div style={{ fontSize: 9, color: '#78716C', marginTop: 2 }}>{g.l}</div>
            <div style={{ fontSize: 10, color: YH.green, fontWeight: 700, marginTop: 2 }}>{g.p}</div>
          </div>
          <div style={{ fontSize: 9, color: YH.amber, fontWeight: 700 }}>★ ٤.٨</div>
        </div>
      ))}
    </div>
  );
}

Object.assign(window, { V1Bold, PhoneHomeContent, SectionHead, FooterCol });
