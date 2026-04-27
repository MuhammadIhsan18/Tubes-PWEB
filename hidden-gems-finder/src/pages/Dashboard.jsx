import React, { useState, useRef, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { Compass, MapPin, LogOut, Search, Heart, User, TrendingUp, Globe, Star, Filter, ArrowRight, Bell, X, ExternalLink, Info } from 'lucide-react';
import SavedView from './views/SavedView';
import TrendingView from './views/TrendingView';
import WorldView from './views/WorldView';
import ProfileView from './views/ProfileView';
import { gems as indonesiaGems, categories as indoCats } from '../data/hiddenGems';

const gems = [...indonesiaGems];
const categories = indoCats;

const navItems = [
  { id:'explore', icon:<MapPin size={20}/>, label:'Jelajahi' },
  { id:'saved', icon:<Heart size={20}/>, label:'Simpan' },
  { id:'trending', icon:<TrendingUp size={20}/>, label:'Trending' },
  { id:'world', icon:<Globe size={20}/>, label:'Dunia' },
  { id:'profile', icon:<User size={20}/>, label:'Profil' },
];

const pageTitles = {
  explore: { title:'Discover', sub:'Temukan destinasi tersembunyi impianmu' },
  saved: { title:'Tersimpan', sub:'Koleksi destinasi favoritmu' },
  trending: { title:'Trending', sub:'Destinasi paling populer saat ini' },
  world: { title:'Dunia', sub:'Jelajahi destinasi dari seluruh penjuru dunia' },
  profile: { title:'Profil', sub:'Kelola akun dan preferensimu' },
};

export default function Dashboard() {
  const navigate = useNavigate();
  const [activeNav, setActiveNav] = useState('explore');
  const [activeCategory, setActiveCategory] = useState('Semua');
  const [liked, setLiked] = useState([]);
  const [search, setSearch] = useState('');
  const [selectedGem, setSelectedGem] = useState(null);
  const sliderRef = useRef(null);
  const [isJumping, setIsJumping] = useState(false);

  // Triplicate gems for infinite effect
  const featuredGems = [...gems].sort((a, b) => b.rating - a.rating).slice(0, 5);
  const infiniteGems = [...featuredGems, ...featuredGems, ...featuredGems];

  useEffect(() => {
    if (sliderRef.current && featuredGems.length > 0) {
      const scrollWidth = sliderRef.current.scrollWidth;
      sliderRef.current.scrollLeft = scrollWidth / 3;
    }
  }, [featuredGems.length]);

  const handleInfiniteScroll = () => {
    if (!sliderRef.current || isJumping) return;
    const { scrollLeft, scrollWidth } = sliderRef.current;
    const oneSetWidth = scrollWidth / 3;

    if (scrollLeft <= 0) {
      setIsJumping(true);
      sliderRef.current.style.scrollBehavior = 'auto';
      sliderRef.current.scrollLeft = oneSetWidth;
      setTimeout(() => {
        if (sliderRef.current) sliderRef.current.style.scrollBehavior = 'smooth';
        setIsJumping(false);
      }, 50);
    } else if (scrollLeft >= oneSetWidth * 2) {
      setIsJumping(true);
      sliderRef.current.style.scrollBehavior = 'auto';
      sliderRef.current.scrollLeft = oneSetWidth;
      setTimeout(() => {
        if (sliderRef.current) sliderRef.current.style.scrollBehavior = 'smooth';
        setIsJumping(false);
      }, 50);
    }
  };

  const scrollSlider = (direction) => {
    if (sliderRef.current) {
      const scrollAmount = sliderRef.current.offsetWidth - 50;
      sliderRef.current.scrollBy({ left: direction === 'left' ? -scrollAmount : scrollAmount, behavior: 'smooth' });
    }
  };

  const currentUser = JSON.parse(localStorage.getItem('hgf_current_user') || '{"name":"Explorer","email":""}');

  const handleLogout = () => {
    localStorage.removeItem('hgf_current_user');
    navigate('/');
  };

  const toggleLike = (id) => setLiked(prev => prev.includes(id) ? prev.filter(x => x !== id) : [...prev, id]);

  const filtered = gems.filter(g => {
    const matchCat = activeCategory === 'Semua' || g.category === activeCategory;
    const matchSearch = g.title.toLowerCase().includes(search.toLowerCase()) || g.location.toLowerCase().includes(search.toLowerCase());
    return matchCat && matchSearch;
  });

  const page = pageTitles[activeNav];

  return (
    <div style={s.root}>
      {/* Modal Detail */}
      {selectedGem && (
        <div style={s.modalBackdrop} onClick={() => setSelectedGem(null)}>
          <div style={s.modal} onClick={e => e.stopPropagation()}>
            <div style={{...s.modalImg, backgroundImage:`url(${selectedGem.img})`}}>
              <div style={s.modalImgOv}/>
              <button onClick={() => setSelectedGem(null)} style={s.modalClose}><X size={18}/></button>
              <div style={s.modalImgInfo}>
                <span style={s.modalCatBadge}>{selectedGem.category}</span>
                <h2 style={s.modalTitle}>{selectedGem.title}</h2>
                <div style={s.modalLocRow}><MapPin size={14}/>{selectedGem.location}</div>
              </div>
            </div>
            <div style={s.modalBody}>
              <div style={s.modalStats}>
                <div style={s.modalStat}><Star size={15} fill='#f59e0b' color='#f59e0b'/><strong>{selectedGem.rating}</strong><span style={{color:'var(--text-muted)',fontSize:'0.78rem'}}>Rating</span></div>
                <div style={s.modalStat}><Info size={15} color='var(--accent-emerald)'/><strong>{selectedGem.reviews}</strong><span style={{color:'var(--text-muted)',fontSize:'0.78rem'}}>Review Google</span></div>
                <div style={s.modalStat}><Heart size={15} color='#ef4444'/><strong style={{fontSize:'0.8rem'}}>{selectedGem.reviews < 300 ? 'Jarang Dikunjungi' : 'Populer'}</strong></div>
              </div>
              {selectedGem.description && <p style={s.modalDesc}>{selectedGem.description}</p>}
              {selectedGem.tips && <div style={s.tipsBox}><span style={s.tipsLabel}>💡 Tips:</span> {selectedGem.tips}</div>}
              {selectedGem.address && <div style={s.addressRow}><MapPin size={13} color='var(--text-muted)'/><span style={{color:'var(--text-muted)',fontSize:'0.82rem'}}>{selectedGem.address}</span></div>}
              {selectedGem.mapUrl && (
                <div style={s.mapSection}>
                  <div style={s.mapHeader}>
                    <Globe size={16} color='var(--accent-emerald)'/>
                    <span style={{fontWeight:'700',fontSize:'0.9rem'}}>Lokasi di Google Maps</span>
                    <a href={selectedGem.mapUrl} target='_blank' rel='noreferrer' style={s.mapLink}><ExternalLink size={14}/> Buka Maps</a>
                  </div>
                  <iframe
                    title={selectedGem.title}
                    src={`https://maps.google.com/maps?q=${encodeURIComponent(selectedGem.title + ' ' + selectedGem.location)}&output=embed`}
                    style={s.mapEmbed}
                    allowFullScreen loading='lazy'
                  />
                </div>
              )}
              <div style={s.modalActions}>
                <button onClick={() => toggleLike(selectedGem.id)} style={{...s.actionBtn, background: liked.includes(selectedGem.id)?'rgba(239,68,68,0.15)':'rgba(255,255,255,0.05)', border:`1px solid ${liked.includes(selectedGem.id)?'#ef4444':'rgba(255,255,255,0.1)'}`}}>
                  <Heart size={16} fill={liked.includes(selectedGem.id)?'#ef4444':'transparent'} color={liked.includes(selectedGem.id)?'#ef4444':'white'}/>
                  {liked.includes(selectedGem.id) ? 'Tersimpan' : 'Simpan'}
                </button>
                {selectedGem.mapUrl && <a href={selectedGem.mapUrl} target='_blank' rel='noreferrer' style={s.actionBtnGreen}><MapPin size={16}/> Lihat di Maps</a>}
              </div>
            </div>
          </div>
        </div>
      )}
      <style>{`
        @keyframes fadeUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
        @keyframes spin { to{transform:rotate(360deg)} }
        .gem-card { animation: fadeUp 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity:0; transition: transform 0.8s, box-shadow 0.8s; }
        .gem-card:hover { transform:translateY(-5px) scale(1.02); box-shadow:0 20px 40px rgba(0,0,0,0.4); }
        .gem-card:hover .card-img { transform:scale(1.07); }
        .nav-btn { display:flex; flex-direction:column; align-items:center; gap:5px; padding:12px 8px; border-radius:14px; cursor:pointer; transition:all 0.2s; border:none; background:transparent; width:100%; }
        .nav-btn:hover { background:rgba(255,255,255,0.05); }
        .nav-btn.active { background:rgba(245,158,11,0.12); }
        .cat-btn { cursor:pointer; padding:7px 18px; border-radius:50px; border:1px solid rgba(245,158,11,0.2); background:transparent; color:var(--text-muted); font-size:0.83rem; font-weight:600; transition:all 0.2s; }
        .cat-btn:hover { border-color:var(--accent-emerald); color:var(--accent-emerald); }
        .cat-btn.active { background:linear-gradient(135deg,var(--accent-emerald),var(--accent-teal)); border-color:transparent; color:white; }
        .sfocus:focus-within { border-color:var(--accent-emerald)!important; box-shadow:0 0 0 3px rgba(245,158,11,0.15)!important; }
        .like-ani { transition:all 0.2s; cursor:pointer; }
        .like-ani:hover { transform:scale(1.25); }
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
        .slider-control { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.1); color: white; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s; z-index: 10; opacity: 0; }
        div:hover > .slider-control { opacity: 1; }
        .slider-control:hover { background: var(--accent-emerald); border-color: transparent; transform: translateY(-50%) scale(1.1); }
      `}</style>

      {/* Sidebar */}
      <aside style={s.sidebar} className="glass-panel animate-fade-in-up">
        <div style={s.logo} className="hover-glow" onClick={() => navigate('/')} title="Home">
          <Compass color="var(--accent-emerald)" size={26} className="animate-float" />
        </div>
        <nav style={s.nav}>
          {navItems.map(item => (
            <button
              key={item.id}
              className={`nav-btn ${activeNav === item.id ? 'active' : ''}`}
              onClick={() => setActiveNav(item.id)}
              style={{ color: activeNav === item.id ? 'var(--accent-emerald)' : 'var(--text-muted)' }}
            >
              {item.icon}
              <span style={{ fontSize:'0.62rem', fontWeight:'700', letterSpacing:'0.3px' }}>{item.label}</span>
            </button>
          ))}
        </nav>
        <div style={s.sideBottom}>
          <div style={s.miniAvatar}>{currentUser.name.charAt(0).toUpperCase()}</div>
          <button className="nav-btn" onClick={handleLogout} style={{ color:'#ef4444' }}>
            <LogOut size={20} />
            <span style={{ fontSize:'0.62rem', fontWeight:'700' }}>Keluar</span>
          </button>
        </div>
      </aside>

      {/* Main */}
      <main style={s.main}>
        {/* Topbar */}
        <header style={s.topbar}>
          <div className="animate-fade-in-up">
            <h1 style={s.pageTitle} className="text-gradient">{page.title}</h1>
            <p style={s.pageSub}>{page.sub}</p>
          </div>
          {activeNav === 'explore' && (
            <div style={s.topRight}>
              <div className="glass-panel sfocus" style={s.searchBox}>
                <Search size={16} color="var(--text-muted)" />
                <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Cari destinasi..." style={s.searchInput} />
              </div>
              <div className="glass-panel" style={s.notifBtn}>
                <Bell size={17} color="var(--text-muted)" />
                <div style={s.notifDot} />
              </div>
              <div style={s.topAvatar}>{currentUser.name.charAt(0).toUpperCase()}</div>
            </div>
          )}
        </header>

        {/* Explore View */}
        {activeNav === 'explore' && (
          <div style={{ display:'flex', flexDirection:'column', gap:'24px' }}>
            {/* Featured + Stats */}
            <div style={s.featuredRow}>
              <div style={{ position: 'relative', flex: 1, minWidth: 0, display: 'flex' }}>
                <div 
                  style={{ ...s.featuredSliderWrapper, height: '100%' }} 
                  className="hide-scroll" 
                  ref={sliderRef}
                  onScroll={handleInfiniteScroll}
                >
                  {infiniteGems.map((fgem, i) => (
                    <div key={`${fgem.id}-${i}`} className="animate-fade-in-up" 
                         style={{ ...s.featuredCard, backgroundImage:`url(${fgem.img})` }}
                         onClick={() => setSelectedGem(fgem)}>
                      <div style={s.featuredOv}>
                        <span style={s.featuredTag}><Star size={12} fill="#f59e0b" color="#f59e0b" /> Destinasi Pilihan</span>
                        <h2 style={s.featuredTitle}>{fgem.title}</h2>
                        <div style={s.featuredMeta}>
                          <MapPin size={13}/>{fgem.location}
                          <span style={{opacity:0.5}}>•</span>
                          <Star size={13} fill="#f59e0b" color="#f59e0b"/>{fgem.rating} ({fgem.reviews} ulasan)
                        </div>
                        <button className="btn-primary" style={s.featuredBtn} onClick={(e) => { e.stopPropagation(); setSelectedGem(fgem); }}>
                          Lihat Detail <ArrowRight size={15}/>
                        </button>
                      </div>
                      <button className="like-ani" onClick={(e) => { e.stopPropagation(); toggleLike(fgem.id); }} style={s.featuredLike}>
                        <Heart size={17} fill={liked.includes(fgem.id)?'#ef4444':'transparent'} color={liked.includes(fgem.id)?'#ef4444':'white'} />
                      </button>
                    </div>
                  ))}
                </div>
                {/* Slider Controls */}
                <button 
                  onClick={() => scrollSlider('left')} 
                  className="slider-control" 
                  style={{ ...s.sliderControl, left: '15px' }}
                >
                  <ArrowRight size={20} style={{ transform: 'rotate(180deg)' }} />
                </button>
                <button 
                  onClick={() => scrollSlider('right')} 
                  className="slider-control" 
                  style={{ ...s.sliderControl, right: '15px' }}
                >
                  <ArrowRight size={20} />
                </button>
              </div>
              <div style={s.statsCol}>
                {[
                  {label:'Total Gems', value:gems.length, color:'var(--accent-emerald)'},
                  {label:'Disimpan', value:liked.length, color:'#ef4444'},
                  {label:'Trending', value:gems.filter(g=>g.tag==='Trending').length, color:'#f59e0b'},
                ].map((st,i) => (
                  <div key={i} className={`glass-panel animate-fade-in-up delay-${(i+2)*100}`} style={s.statCard}>
                    <div style={{fontSize:'1.7rem', fontWeight:'800', color:st.color}}>{st.value}</div>
                    <div style={{color:'var(--text-muted)', fontSize:'0.8rem'}}>{st.label}</div>
                  </div>
                ))}
              </div>
            </div>



            {/* Grid */}
            <div style={s.grid}>
              {filtered.map((gem, i) => (
                <div key={gem.id} className="gem-card glass-panel" onClick={() => setSelectedGem(gem)} style={{...s.card, animationDelay:`${i*0.07}s`}}>
                  <div style={s.cardImgWrap}>
                    <div className="card-img" style={{...s.cardImg, backgroundImage:`url(${gem.img})`}} />
                    <div style={s.cardOv}/>
                    {gem.tag && <span style={s.tagBadge}>{gem.tag}</span>}
                    <button className="like-ani" onClick={e => { e.stopPropagation(); toggleLike(gem.id); }} style={s.cardLike}>
                      <Heart size={15} fill={liked.includes(gem.id)?'#ef4444':'transparent'} color={liked.includes(gem.id)?'#ef4444':'white'}/>
                    </button>
                  </div>
                  <div style={s.cardBody}>
                    <span style={s.cardCat}>{gem.category}</span>
                    <h3 style={s.cardTitle}>{gem.title}</h3>
                    <div style={s.cardMeta}>
                      <span style={s.cardLoc}><MapPin size={12}/>{gem.location}</span>
                      <span style={s.cardRating}><Star size={12} fill="#f59e0b" color="#f59e0b"/>{gem.rating}</span>
                    </div>
                    {gem.reviews && <div style={{marginTop:'6px',fontSize:'0.72rem',color:'var(--text-muted)'}}>{gem.reviews} ulasan Google</div>}
                  </div>
                </div>
              ))}
              {filtered.length === 0 && (
                <div style={s.empty}>
                  <Search size={40} color="var(--text-muted)"/>
                  <p style={{marginTop:'12px', color:'var(--text-muted)'}}>Tidak ada hasil untuk "{search}"</p>
                </div>
              )}
            </div>
          </div>
        )}

        {activeNav === 'saved' && <SavedView gems={gems} liked={liked} toggleLike={toggleLike} onSelect={setSelectedGem} />}
        {activeNav === 'trending' && <TrendingView gems={gems} liked={liked} toggleLike={toggleLike} onSelect={setSelectedGem} />}
        {activeNav === 'world' && <WorldView gems={gems} liked={liked} toggleLike={toggleLike} onSelect={setSelectedGem} />}
        {activeNav === 'profile' && <ProfileView gems={gems} liked={liked} />}
      </main>
    </div>
  );
}

const s = {
  root: { display:'flex', minHeight:'100vh', padding:'16px', gap:'16px', background:'var(--bg-primary)' },
  sidebar: { width:'76px', borderRadius:'24px', display:'flex', flexDirection:'column', alignItems:'center', padding:'18px 0', position:'sticky', top:'16px', height:'calc(100vh - 32px)', flexShrink:0 },
  logo: { marginBottom:'28px', cursor:'pointer', padding:'6px' },
  nav: { display:'flex', flexDirection:'column', gap:'2px', width:'100%', padding:'0 6px', flex:1 },
  sideBottom: { display:'flex', flexDirection:'column', alignItems:'center', gap:'4px', padding:'0 6px', width:'100%' },
  miniAvatar: { width:'40px', height:'40px', borderRadius:'50%', background:'linear-gradient(135deg,var(--accent-emerald),var(--accent-teal))', display:'flex', alignItems:'center', justifyContent:'center', fontWeight:'800', fontSize:'1rem', color:'white', marginBottom:'4px' },
  main: { flex:1, display:'flex', flexDirection:'column', gap:'22px', minWidth:0, paddingBottom:'24px' },
  topbar: { display:'flex', justifyContent:'space-between', alignItems:'center', paddingTop:'8px', flexWrap:'wrap', gap:'12px' },
  pageTitle: { fontSize:'1.9rem', fontWeight:'800', margin:0 },
  pageSub: { color:'var(--text-muted)', fontSize:'0.88rem', marginTop:'2px' },
  topRight: { display:'flex', alignItems:'center', gap:'10px' },
  searchBox: { display:'flex', alignItems:'center', gap:'8px', padding:'9px 14px', borderRadius:'12px', transition:'all 0.3s' },
  searchInput: { border:'none', background:'transparent', color:'white', outline:'none', fontSize:'0.88rem', width:'170px' },
  notifBtn: { width:'40px', height:'40px', borderRadius:'12px', display:'flex', alignItems:'center', justifyContent:'center', cursor:'pointer', position:'relative' },
  notifDot: { position:'absolute', top:'9px', right:'9px', width:'7px', height:'7px', borderRadius:'50%', background:'var(--accent-emerald)' },
  topAvatar: { width:'40px', height:'40px', borderRadius:'50%', background:'linear-gradient(135deg,var(--accent-emerald),var(--accent-teal))', display:'flex', alignItems:'center', justifyContent:'center', fontWeight:'800', fontSize:'0.95rem', color:'white', cursor:'pointer' },
  featuredRow: { display:'flex', gap:'16px', minHeight:'230px', width: '100%' },
  featuredSliderWrapper: { flex:1, display:'flex', gap:'16px', overflowX:'auto', scrollSnapType:'x mandatory', scrollBehavior:'smooth', minWidth: 0, paddingBottom: '10px', height: '100%' },
  featuredCard: { flexShrink: 0, minWidth:'calc(100% - 30px)', height: '100%', minHeight: '220px', scrollSnapAlign:'start', borderRadius:'20px', backgroundSize:'cover', backgroundPosition:'center', position:'relative', overflow:'hidden', cursor:'pointer' },
  featuredOv: { position:'absolute', inset:0, background:'linear-gradient(to top, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.1) 60%)', display:'flex', flexDirection:'column', justifyContent:'flex-end', padding:'22px' },
  featuredTag: { display:'inline-flex', alignItems:'center', gap:'5px', background:'rgba(245,158,11,0.2)', border:'1px solid rgba(245,158,11,0.4)', color:'#f59e0b', fontSize:'0.73rem', fontWeight:'700', padding:'4px 12px', borderRadius:'50px', marginBottom:'8px', width:'fit-content' },
  featuredTitle: { fontSize:'1.8rem', fontWeight:'800', margin:'0 0 6px', color:'white' },
  featuredMeta: { display:'flex', alignItems:'center', gap:'7px', color:'rgba(255,255,255,0.8)', fontSize:'0.82rem', marginBottom:'14px' },
  featuredBtn: { display:'inline-flex', alignItems:'center', gap:'6px', padding:'9px 18px', fontSize:'0.83rem', borderRadius:'10px', width:'fit-content' },
  featuredLike: { position:'absolute', top:'14px', right:'14px', background:'rgba(0,0,0,0.4)', backdropFilter:'blur(8px)', padding:'9px', borderRadius:'50%', display:'flex', border:'none' },
  statsCol: { display:'flex', flexDirection:'column', gap:'10px', width:'190px', flexShrink:0 },
  statCard: { display:'flex', flexDirection:'column', justifyContent:'center', padding:'14px 20px', borderRadius:'16px', flex:1 },
  filterRow: { display:'flex', justifyContent:'space-between', alignItems:'center', gap:'10px', flexWrap:'wrap' },
  cats: { display:'flex', gap:'8px', flexWrap:'wrap' },
  filterBtn: { display:'flex', alignItems:'center', gap:'8px', padding:'7px 14px', borderRadius:'10px', cursor:'pointer' },
  grid: { display:'grid', gridTemplateColumns:'repeat(auto-fill, minmax(210px, 1fr))', gap:'18px' },
  card: { overflow:'hidden', padding:'0', borderRadius:'16px', cursor:'pointer' },
  cardImgWrap: { position:'relative', height:'175px', overflow:'hidden' },
  cardImg: { width:'100%', height:'100%', backgroundSize:'cover', backgroundPosition:'center' },
  cardOv: { position:'absolute', inset:0, background:'linear-gradient(to top, rgba(0,0,0,0.5) 0%, transparent 60%)' },
  tagBadge: { position:'absolute', top:'10px', left:'10px', background:'rgba(245,158,11,0.85)', backdropFilter:'blur(4px)', color:'white', fontSize:'0.68rem', fontWeight:'700', padding:'3px 10px', borderRadius:'50px' },
  cardLike: { position:'absolute', top:'10px', right:'10px', background:'rgba(0,0,0,0.35)', backdropFilter:'blur(4px)', padding:'7px', borderRadius:'50%', display:'flex', border:'none' },
  cardBody: { padding:'14px' },
  cardCat: { fontSize:'0.68rem', fontWeight:'700', color:'var(--accent-teal)', textTransform:'uppercase', letterSpacing:'0.5px', display:'block', marginBottom:'5px' },
  cardTitle: { fontSize:'1rem', fontWeight:'700', margin:'0 0 9px' },
  cardMeta: { display:'flex', justifyContent:'space-between', alignItems:'center' },
  cardLoc: { display:'flex', alignItems:'center', gap:'4px', color:'var(--text-muted)', fontSize:'0.8rem' },
  cardRating: { display:'flex', alignItems:'center', gap:'4px', fontSize:'0.8rem', fontWeight:'700' },
  empty: { gridColumn:'1/-1', display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', padding:'60px', color:'var(--text-muted)' },
  // Modal
  modalBackdrop: { position:'fixed', inset:0, background:'rgba(0,0,0,0.75)', backdropFilter:'blur(6px)', zIndex:1000, display:'flex', alignItems:'center', justifyContent:'center', padding:'20px' },
  modal: { background:'#111827', border:'1px solid rgba(255,255,255,0.08)', borderRadius:'24px', width:'100%', maxWidth:'560px', maxHeight:'90vh', overflowY:'auto', boxShadow:'0 40px 80px rgba(0,0,0,0.6)' },
  modalImg: { height:'240px', backgroundSize:'cover', backgroundPosition:'center', position:'relative', borderRadius:'24px 24px 0 0', overflow:'hidden' },
  modalImgOv: { position:'absolute', inset:0, background:'linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.1) 60%)' },
  modalClose: { position:'absolute', top:'14px', right:'14px', background:'rgba(0,0,0,0.5)', border:'none', borderRadius:'50%', padding:'8px', cursor:'pointer', color:'white', display:'flex', zIndex:2 },
  modalImgInfo: { position:'absolute', bottom:0, left:0, right:0, padding:'20px', zIndex:2 },
  modalCatBadge: { background:'rgba(245,158,11,0.8)', color:'white', fontSize:'0.7rem', fontWeight:'700', padding:'3px 12px', borderRadius:'50px', display:'inline-block', marginBottom:'8px' },
  modalTitle: { fontSize:'1.5rem', fontWeight:'800', margin:'0 0 6px', color:'white' },
  modalLocRow: { display:'flex', alignItems:'center', gap:'5px', color:'rgba(255,255,255,0.75)', fontSize:'0.83rem' },
  modalBody: { padding:'20px', display:'flex', flexDirection:'column', gap:'16px' },
  modalStats: { display:'flex', gap:'12px' },
  modalStat: { flex:1, background:'rgba(255,255,255,0.04)', border:'1px solid rgba(255,255,255,0.07)', borderRadius:'12px', padding:'12px', display:'flex', flexDirection:'column', alignItems:'center', gap:'4px', fontSize:'1rem', fontWeight:'700' },
  modalDesc: { color:'var(--text-muted)', fontSize:'0.88rem', lineHeight:'1.65', margin:0 },
  tipsBox: { background:'rgba(245,158,11,0.07)', border:'1px solid rgba(245,158,11,0.2)', borderRadius:'12px', padding:'12px 16px', fontSize:'0.85rem', color:'var(--text-muted)', lineHeight:'1.6' },
  tipsLabel: { color:'var(--accent-emerald)', fontWeight:'700' },
  addressRow: { display:'flex', alignItems:'center', gap:'6px' },
  mapSection: { display:'flex', flexDirection:'column', gap:'10px' },
  mapHeader: { display:'flex', alignItems:'center', gap:'8px' },
  mapLink: { marginLeft:'auto', color:'var(--accent-emerald)', fontSize:'0.82rem', display:'flex', alignItems:'center', gap:'4px', textDecoration:'none', fontWeight:'600' },
  mapEmbed: { width:'100%', height:'220px', borderRadius:'12px', border:'none' },
  modalActions: { display:'flex', gap:'10px' },
  actionBtn: { flex:1, padding:'11px', borderRadius:'12px', cursor:'pointer', color:'white', fontWeight:'600', fontSize:'0.88rem', display:'flex', alignItems:'center', justifyContent:'center', gap:'7px' },
  actionBtnGreen: { flex:1, padding:'11px', borderRadius:'12px', background:'linear-gradient(135deg,var(--accent-emerald),var(--accent-teal))', color:'white', fontWeight:'600', fontSize:'0.88rem', display:'flex', alignItems:'center', justifyContent:'center', gap:'7px', textDecoration:'none' },
};
