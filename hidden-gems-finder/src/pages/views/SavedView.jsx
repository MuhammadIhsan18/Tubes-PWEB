import React from 'react';
import { Heart, MapPin, Star, Inbox } from 'lucide-react';

export default function SavedView({ gems, liked, toggleLike, onSelect }) {
  const savedGems = gems.filter(g => liked.includes(g.id));

  if (savedGems.length === 0) return (
    <div style={s.empty}>
      <div style={s.emptyIcon}><Inbox size={48} color="var(--accent-emerald)" /></div>
      <h2 style={{ margin: '20px 0 8px' }}>Belum Ada yang Disimpan</h2>
      <p style={{ color: 'var(--text-muted)' }}>Klik ikon ❤️ pada kartu destinasi untuk menyimpannya di sini.</p>
    </div>
  );

  return (
    <div>
      <div style={s.header}>
        <h2 style={s.title}>Destinasi Tersimpan</h2>
        <span style={s.badge}>{savedGems.length} tempat</span>
      </div>
      <div style={s.grid}>
        {savedGems.map((gem, i) => (
          <div key={gem.id} className="glass-panel gem-card" onClick={() => onSelect && onSelect(gem)}
               style={{ ...s.card, animationDelay: `${i*0.07}s` }}>
            <div style={s.imgWrap}>
              <div style={{ ...s.img, backgroundImage: `url(${gem.img})` }} />
              <div style={s.overlay} />
              <button onClick={e => { e.stopPropagation(); toggleLike(gem.id); }} style={s.likeBtn}>
                <Heart size={16} fill="#ef4444" color="#ef4444" />
              </button>
            </div>
            <div style={s.body}>
              <span style={s.cat}>{gem.category}</span>
              <h3 style={s.cardTitle}>{gem.title}</h3>
              <div style={s.meta}>
                <span style={s.loc}><MapPin size={13} />{gem.location}</span>
                <span style={s.rating}><Star size={13} fill="#f59e0b" color="#f59e0b" />{gem.rating}</span>
              </div>
              {gem.reviews && <div style={{marginTop:'5px',fontSize:'0.72rem',color:'var(--text-muted)'}}>{gem.reviews} ulasan Google</div>}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

const s = {
  empty: { display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', minHeight:'60vh', textAlign:'center' },
  emptyIcon: { width:'90px', height:'90px', borderRadius:'50%', background:'rgba(245,158,11,0.1)', border:'1px solid rgba(245,158,11,0.3)', display:'flex', alignItems:'center', justifyContent:'center' },
  header: { display:'flex', alignItems:'center', gap:'12px', marginBottom:'24px' },
  title: { fontSize:'1.6rem', fontWeight:'800', margin:0 },
  badge: { background:'rgba(245,158,11,0.1)', border:'1px solid rgba(245,158,11,0.3)', color:'var(--accent-emerald)', padding:'4px 14px', borderRadius:'50px', fontSize:'0.85rem', fontWeight:'600' },
  grid: { display:'grid', gridTemplateColumns:'repeat(auto-fill, minmax(220px, 1fr))', gap:'20px', paddingBottom:'24px' },
  card: { overflow:'hidden', padding:'0', borderRadius:'16px', cursor:'pointer' },
  imgWrap: { position:'relative', height:'180px', overflow:'hidden' },
  img: { width:'100%', height:'100%', backgroundSize:'cover', backgroundPosition:'center', transition:'transform 0.4s ease' },
  overlay: { position:'absolute', inset:0, background:'linear-gradient(to top, rgba(0,0,0,0.5) 0%, transparent 60%)' },
  likeBtn: { position:'absolute', top:'10px', right:'10px', background:'rgba(0,0,0,0.35)', backdropFilter:'blur(4px)', padding:'8px', borderRadius:'50%', display:'flex', border:'none', cursor:'pointer' },
  body: { padding:'16px' },
  cat: { fontSize:'0.7rem', fontWeight:'700', color:'var(--accent-teal)', textTransform:'uppercase', letterSpacing:'0.5px', display:'block', marginBottom:'6px' },
  cardTitle: { fontSize:'1.05rem', fontWeight:'700', margin:'0 0 10px' },
  meta: { display:'flex', justifyContent:'space-between', alignItems:'center' },
  loc: { display:'flex', alignItems:'center', gap:'4px', color:'var(--text-muted)', fontSize:'0.82rem' },
  rating: { display:'flex', alignItems:'center', gap:'4px', fontSize:'0.82rem', fontWeight:'700' },
};
