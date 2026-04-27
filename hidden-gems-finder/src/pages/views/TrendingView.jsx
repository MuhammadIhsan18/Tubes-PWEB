import React from 'react';
import { TrendingUp, MapPin, Star, Heart, Flame, Award, Zap, ExternalLink, Eye } from 'lucide-react';
import { gems as indoGems } from '../../data/hiddenGems';

export default function TrendingView({ gems, liked, toggleLike, onSelect }) {
  // Paling jarang dikunjungi = review paling sedikit
  const rarestGems = [...indoGems].sort((a, b) => a.reviews - b.reviews).slice(0, 5);
  // Top rated hidden gems
  const topRated = [...indoGems].sort((a, b) => b.rating - a.rating).slice(0, 3);

  return (
    <div style={s.root}>
      {/* Top Rated */}
      <div style={s.section}>
        <div style={s.sectionHead}>
          <Award size={20} color="#f59e0b" />
          <h2 style={s.sectionTitle}>Top Rated Hidden Gems</h2>
        </div>
        <div style={s.podium}>
          {topRated.map((gem, i) => (
            <div key={gem.id} className="glass-panel" onClick={() => onSelect && onSelect(gem)}
                 style={{ ...s.podiumCard, animationDelay: `${i*0.1}s` }}>
              <div style={s.rankBadge}>{['🥇','🥈','🥉'][i]}</div>
              <div style={{ ...s.podiumImg, backgroundImage: `url(${gem.img})` }} />
              <div style={s.podiumOverlay} />
              <div style={s.podiumInfo}>
                <h3 style={s.podiumTitle}>{gem.title}</h3>
                <div style={s.podiumMeta}>
                  <MapPin size={13} />{gem.location}
                  <span style={s.dot}>•</span>
                  <Star size={13} fill="#f59e0b" color="#f59e0b" />{gem.rating}
                </div>
                <div style={{fontSize:'0.72rem',color:'rgba(255,255,255,0.6)',marginTop:'3px'}}>{gem.reviews} ulasan Google</div>
              </div>
              <button onClick={e => { e.stopPropagation(); toggleLike(gem.id); }} style={s.likeBtn}>
                <Heart size={16} fill={liked.includes(gem.id)?'#ef4444':'transparent'} color={liked.includes(gem.id)?'#ef4444':'white'} />
              </button>
            </div>
          ))}
        </div>
      </div>

      {/* Paling Jarang Dikunjungi */}
      <div style={s.section}>
        <div style={s.sectionHead}>
          <Eye size={20} color="#8b5cf6" />
          <h2 style={s.sectionTitle}>Paling Jarang Dikunjungi</h2>
          <span style={s.badge}>Berdasarkan Jumlah Review Google</span>
        </div>
        <div style={s.trendList}>
          {rarestGems.map((gem, i) => (
            <div key={gem.id} className="glass-panel gem-card" onClick={() => onSelect && onSelect(gem)}
                 style={{ ...s.trendCard, animationDelay: `${i*0.08}s` }}>
              <div style={{ ...s.trendImg, backgroundImage: `url(${gem.img})` }} />
              <div style={s.trendBody}>
                <div style={s.trendTop}>
                  <span style={s.rankNum}>#{i+1}</span>
                  <span style={{ ...s.tagBadge, background:'rgba(139,92,246,0.15)', color:'#a78bfa' }}>
                    <Eye size={12} /> {gem.reviews} ulasan
                  </span>
                </div>
                <h3 style={s.trendTitle}>{gem.title}</h3>
                <div style={s.trendMeta}>
                  <span style={s.loc}><MapPin size={13} />{gem.location}</span>
                  <span style={s.cat}>{gem.category}</span>
                </div>
                <div style={{fontSize:'0.75rem',color:'var(--accent-emerald)',marginTop:'4px',fontWeight:'600'}}>✨ Hidden gem — belum banyak diketahui!</div>
              </div>
              <div style={s.trendRight}>
                <div style={s.ratingBig}>{gem.rating}</div>
                <div style={s.stars}>{'★'.repeat(Math.round(gem.rating))}</div>
                <button onClick={e => { e.stopPropagation(); toggleLike(gem.id); }} style={s.trendLike}>
                  <Heart size={16} fill={liked.includes(gem.id)?'#ef4444':'transparent'} color={liked.includes(gem.id)?'#ef4444':'var(--text-muted)'} />
                </button>
                {gem.mapUrl && (
                  <a href={gem.mapUrl} target="_blank" rel="noreferrer" onClick={e => e.stopPropagation()} style={s.mapsBtn}>
                    <ExternalLink size={12}/> Maps
                  </a>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

const s = {
  root: { display:'flex', flexDirection:'column', gap:'32px' },
  section: { display:'flex', flexDirection:'column', gap:'16px' },
  sectionHead: { display:'flex', alignItems:'center', gap:'10px' },
  sectionTitle: { fontSize:'1.4rem', fontWeight:'800', margin:0 },
  badge: { background:'rgba(139,92,246,0.1)', border:'1px solid rgba(139,92,246,0.3)', color:'#a78bfa', padding:'3px 12px', borderRadius:'50px', fontSize:'0.78rem', fontWeight:'600', marginLeft:'auto' },
  podium: { display:'grid', gridTemplateColumns:'repeat(3, 1fr)', gap:'16px' },
  podiumCard: { position:'relative', borderRadius:'16px', overflow:'hidden', height:'220px', cursor:'pointer' },
  podiumImg: { width:'100%', height:'100%', backgroundSize:'cover', backgroundPosition:'center', position:'absolute', inset:0, transition:'transform 0.4s' },
  podiumOverlay: { position:'absolute', inset:0, background:'linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.1) 60%)' },
  rankBadge: { position:'absolute', top:'12px', left:'12px', fontSize:'1.5rem', zIndex:2, filter:'drop-shadow(0 2px 4px rgba(0,0,0,0.5))' },
  podiumInfo: { position:'absolute', bottom:0, left:0, right:0, padding:'16px', zIndex:2 },
  podiumTitle: { fontSize:'1rem', fontWeight:'700', margin:'0 0 4px', color:'white' },
  podiumMeta: { display:'flex', alignItems:'center', gap:'6px', color:'rgba(255,255,255,0.75)', fontSize:'0.82rem' },
  dot: { opacity:0.5 },
  likeBtn: { position:'absolute', top:'12px', right:'12px', background:'rgba(0,0,0,0.4)', backdropFilter:'blur(4px)', padding:'8px', borderRadius:'50%', border:'none', cursor:'pointer', display:'flex', zIndex:2 },
  trendList: { display:'flex', flexDirection:'column', gap:'12px' },
  trendCard: { display:'flex', alignItems:'center', gap:'16px', padding:'0', borderRadius:'14px', overflow:'hidden', cursor:'pointer' },
  trendImg: { width:'90px', height:'90px', backgroundSize:'cover', backgroundPosition:'center', flexShrink:0 },
  trendBody: { flex:1, padding:'12px 0', display:'flex', flexDirection:'column', gap:'4px' },
  trendTop: { display:'flex', alignItems:'center', gap:'8px' },
  rankNum: { color:'var(--text-muted)', fontSize:'0.85rem', fontWeight:'700' },
  tagBadge: { display:'inline-flex', alignItems:'center', gap:'4px', padding:'2px 10px', borderRadius:'50px', fontSize:'0.75rem', fontWeight:'700' },
  trendTitle: { fontSize:'1rem', fontWeight:'700', margin:0 },
  trendMeta: { display:'flex', alignItems:'center', gap:'12px', color:'var(--text-muted)', fontSize:'0.82rem' },
  loc: { display:'flex', alignItems:'center', gap:'4px' },
  cat: { fontSize:'0.75rem', background:'rgba(6,182,212,0.1)', color:'var(--accent-teal)', padding:'1px 8px', borderRadius:'50px', fontWeight:'600' },
  trendRight: { display:'flex', flexDirection:'column', alignItems:'center', gap:'4px', padding:'12px 16px' },
  ratingBig: { fontSize:'1.4rem', fontWeight:'800', color:'#f59e0b' },
  stars: { fontSize:'0.7rem', color:'#f59e0b', letterSpacing:'1px' },
  trendLike: { background:'transparent', border:'none', cursor:'pointer', display:'flex', padding:'4px', marginTop:'4px' },
  mapsBtn: { color:'var(--accent-emerald)', fontSize:'0.72rem', display:'flex', alignItems:'center', gap:'3px', textDecoration:'none', fontWeight:'600', marginTop:'2px' },
};
