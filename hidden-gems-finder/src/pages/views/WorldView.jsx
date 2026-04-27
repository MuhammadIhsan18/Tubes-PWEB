import React, { useState } from 'react';
import { Globe, MapPin, Star, Heart, ChevronDown, ChevronUp, ExternalLink } from 'lucide-react';
import { gems as indoGems, regions } from '../../data/hiddenGems';

export default function WorldView({ gems, liked, toggleLike, onSelect }) {
  const [openRegion, setOpenRegion] = useState('Jawa');

  const regionColors = {
    'Jawa': 'var(--accent-emerald)', 'Sumatera': 'var(--accent-teal)',
    'Bali & Nusa Tenggara': '#f59e0b', 'Kalimantan': '#8b5cf6', 
    'Sulawesi': '#ec4899', 'Maluku & Papua': '#ef4444',
    'Asia': '#f43f5e', 'Eropa': '#3b82f6', 'Amerika': '#eab308', 'Afrika': '#d946ef'
  };

  const regionEmoji = {
    'Jawa': '🏝️', 'Sumatera': '🌋', 'Bali & Nusa Tenggara': '🌺', 
    'Kalimantan': '🌿', 'Sulawesi': '⛵', 'Maluku & Papua': '🐚',
    'Asia': '🏮', 'Eropa': '🏰', 'Amerika': '🏜️', 'Afrika': '🐪'
  };

  return (
    <div style={s.root}>
      <div style={s.header}>
        <Globe size={22} color="var(--accent-emerald)" />
        <h2 style={s.title}>Peta Hidden Gems Indonesia</h2>
        <span style={s.totalBadge}>{indoGems.length} destinasi tersembunyi</span>
      </div>

      {/* Stats Bar */}
      <div className="glass-panel" style={s.statsBar}>
        {Object.entries(regions).map(([region, list]) => (
          <div key={region} style={s.statItem} onClick={() => setOpenRegion(region)}>
            <div style={{ ...s.statDot, background: regionColors[region] }} />
            <div>
              <div style={s.statNum}>{list.length}</div>
              <div style={s.statLabel}>{region}</div>
            </div>
          </div>
        ))}
      </div>

      {/* Accordion by Region */}
      <div style={s.accordion}>
        {Object.entries(regions).map(([region, list]) => {
          const isOpen = openRegion === region;
          const color = regionColors[region];
          return (
            <div key={region} className="glass-panel" style={s.regionCard}>
              <button style={s.regionHead} onClick={() => setOpenRegion(isOpen ? null : region)}>
                <div style={{ display:'flex', alignItems:'center', gap:'12px' }}>
                  <div style={{ ...s.regionIcon, background: `${color}20`, border: `1px solid ${color}40` }}>
                    <span style={{ fontSize:'1.3rem' }}>{regionEmoji[region]}</span>
                  </div>
                  <div>
                    <div style={{ fontWeight:'700', fontSize:'1rem' }}>{region}</div>
                    <div style={{ color:'var(--text-muted)', fontSize:'0.82rem' }}>{list.length} hidden gem{list.length > 1 ? 's' : ''}</div>
                  </div>
                </div>
                {isOpen ? <ChevronUp size={20} color="var(--text-muted)" /> : <ChevronDown size={20} color="var(--text-muted)" />}
              </button>

              {isOpen && (
                <div style={s.regionBody}>
                  {list.map((gem, i) => (
                    <div key={gem.id} style={{ ...s.gemRow, animationDelay:`${i*0.06}s` }} className="gem-card"
                         onClick={() => onSelect && onSelect(gem)}>
                      <div style={{ ...s.gemImg, backgroundImage:`url(${gem.img})` }} />
                      <div style={s.gemInfo}>
                        <h4 style={s.gemName}>{gem.title}</h4>
                        <div style={s.gemMeta}>
                          <span style={s.gemLoc}><MapPin size={12} />{gem.location}</span>
                          <span style={s.gemCat}>{gem.category}</span>
                        </div>
                        <div style={{color:'var(--text-muted)',fontSize:'0.75rem',marginTop:'3px'}}>{gem.reviews} ulasan Google · Jarang dikunjungi</div>
                      </div>
                      <div style={s.gemRight}>
                        <div style={s.gemRating}><Star size={13} fill="#f59e0b" color="#f59e0b" />{gem.rating}</div>
                        <button onClick={e => { e.stopPropagation(); toggleLike(gem.id); }} style={s.likeBtn}>
                          <Heart size={15} fill={liked.includes(gem.id)?'#ef4444':'transparent'} color={liked.includes(gem.id)?'#ef4444':'var(--text-muted)'} />
                        </button>
                        {gem.mapUrl && (
                          <a href={gem.mapUrl} target="_blank" rel="noreferrer" onClick={e => e.stopPropagation()} style={s.mapsLink}>
                            <ExternalLink size={13}/> Maps
                          </a>
                        )}
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );
}

const s = {
  root: { display:'flex', flexDirection:'column', gap:'20px' },
  header: { display:'flex', alignItems:'center', gap:'10px' },
  title: { fontSize:'1.6rem', fontWeight:'800', margin:0 },
  totalBadge: { marginLeft:'auto', color:'var(--text-muted)', fontSize:'0.85rem' },
  statsBar: { display:'flex', gap:'0', borderRadius:'16px', overflow:'hidden', cursor:'pointer' },
  statItem: { flex:1, display:'flex', alignItems:'center', gap:'12px', padding:'16px 20px', borderRight:'1px solid var(--glass-border)', transition:'background 0.2s' },
  statDot: { width:'10px', height:'10px', borderRadius:'50%', flexShrink:0 },
  statNum: { fontSize:'1.4rem', fontWeight:'800' },
  statLabel: { fontSize:'0.75rem', color:'var(--text-muted)', marginTop:'2px' },
  accordion: { display:'flex', flexDirection:'column', gap:'12px' },
  regionCard: { borderRadius:'16px', overflow:'hidden' },
  regionHead: { width:'100%', display:'flex', justifyContent:'space-between', alignItems:'center', padding:'18px 20px', background:'transparent', border:'none', cursor:'pointer', color:'var(--text-main)', textAlign:'left' },
  regionIcon: { width:'44px', height:'44px', borderRadius:'12px', display:'flex', alignItems:'center', justifyContent:'center' },
  regionBody: { padding:'0 16px 16px', display:'flex', flexDirection:'column', gap:'10px' },
  gemRow: { display:'flex', alignItems:'center', gap:'12px', background:'rgba(255,255,255,0.03)', borderRadius:'12px', overflow:'hidden', cursor:'pointer', transition:'background 0.2s' },
  gemImg: { width:'70px', height:'70px', backgroundSize:'cover', backgroundPosition:'center', flexShrink:0 },
  gemInfo: { flex:1, padding:'10px 0' },
  gemName: { fontSize:'0.95rem', fontWeight:'700', margin:'0 0 4px' },
  gemMeta: { display:'flex', alignItems:'center', gap:'10px' },
  gemLoc: { display:'flex', alignItems:'center', gap:'4px', color:'var(--text-muted)', fontSize:'0.8rem' },
  gemCat: { background:'rgba(6,182,212,0.1)', color:'var(--accent-teal)', fontSize:'0.72rem', fontWeight:'600', padding:'2px 8px', borderRadius:'50px' },
  gemRight: { display:'flex', flexDirection:'column', alignItems:'center', gap:'6px', padding:'10px 14px' },
  gemRating: { display:'flex', alignItems:'center', gap:'4px', fontWeight:'700', fontSize:'0.85rem' },
  likeBtn: { background:'transparent', border:'none', cursor:'pointer', display:'flex', padding:'2px' },
  mapsLink: { color:'var(--accent-emerald)', fontSize:'0.72rem', display:'flex', alignItems:'center', gap:'3px', textDecoration:'none', fontWeight:'600' },
};
