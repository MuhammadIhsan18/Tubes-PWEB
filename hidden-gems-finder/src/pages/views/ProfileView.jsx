import React, { useState } from 'react';
import { User, Mail, MapPin, Heart, Star, Edit3, Camera, LogOut, Shield, Bell, Globe } from 'lucide-react';
import { useNavigate } from 'react-router-dom';

export default function ProfileView({ gems, liked }) {
  const navigate = useNavigate();
  const currentUser = JSON.parse(localStorage.getItem('hgf_current_user') || '{"name":"Explorer","email":""}');
  const [editMode, setEditMode] = useState(false);
  const [name, setName] = useState(currentUser.name);
  const [bio, setBio] = useState('Petualang sejati yang selalu mencari tempat tersembunyi di dunia 🌍');

  const savedGems = gems.filter(g => liked.includes(g.id));
  const topGem = savedGems[0];

  const saveProfile = () => {
    const updated = { ...currentUser, name };
    localStorage.setItem('hgf_current_user', JSON.stringify(updated));
    setEditMode(false);
  };

  const handleLogout = () => {
    localStorage.removeItem('hgf_current_user');
    navigate('/');
  };

  const stats = [
    { label:'Tersimpan', value: liked.length, color:'#ef4444' },
    { label:'Dikunjungi', value: Math.floor(liked.length * 1.5) + 2, color:'var(--accent-emerald)' },
    { label:'Ulasan', value: liked.length + 1, color:'#f59e0b' },
  ];

  return (
    <div style={s.root}>
      {/* Profile Hero */}
      <div className="glass-panel" style={s.hero}>
        <div style={s.coverBg} />
        <div style={s.heroContent}>
          <div style={s.avatarWrap}>
            <div style={s.avatar}>{currentUser.name.charAt(0).toUpperCase()}</div>
            <div style={s.cameraBtn}><Camera size={14} color="white" /></div>
          </div>
          <div style={s.heroInfo}>
            {editMode ? (
              <input value={name} onChange={e => setName(e.target.value)} style={s.nameInput} />
            ) : (
              <h2 style={s.userName}>{currentUser.name}</h2>
            )}
            <div style={s.userEmail}><Mail size={14} />{currentUser.email}</div>
            {editMode ? (
              <textarea value={bio} onChange={e => setBio(e.target.value)} style={s.bioInput} />
            ) : (
              <p style={s.bio}>{bio}</p>
            )}
          </div>
          <div style={s.heroActions}>
            {editMode ? (
              <>
                <button className="btn-primary" style={s.editBtn} onClick={saveProfile}>Simpan</button>
                <button className="btn-secondary" style={s.editBtn} onClick={() => setEditMode(false)}>Batal</button>
              </>
            ) : (
              <button className="btn-secondary" style={s.editBtn} onClick={() => setEditMode(true)}>
                <Edit3 size={15} /> Edit Profil
              </button>
            )}
          </div>
        </div>

        {/* Stats */}
        <div style={s.statsRow}>
          {stats.map((st, i) => (
            <div key={i} style={s.statItem}>
              <div style={{ ...s.statVal, color: st.color }}>{st.value}</div>
              <div style={s.statLabel}>{st.label}</div>
            </div>
          ))}
        </div>
      </div>

      <div style={s.grid}>
        {/* Saved Preview */}
        <div className="glass-panel" style={s.section}>
          <div style={s.secHead}>
            <Heart size={18} color="#ef4444" />
            <h3 style={s.secTitle}>Destinasi Favorit</h3>
            <span style={s.count}>{savedGems.length}</span>
          </div>
          {savedGems.length === 0 ? (
            <p style={s.empty}>Belum ada destinasi yang disimpan.</p>
          ) : (
            <div style={s.savedList}>
              {savedGems.slice(0, 4).map(gem => (
                <div key={gem.id} style={s.savedRow}>
                  <div style={{ ...s.savedImg, backgroundImage:`url(${gem.img})` }} />
                  <div>
                    <div style={s.savedName}>{gem.title}</div>
                    <div style={s.savedLoc}><MapPin size={12} />{gem.location}</div>
                  </div>
                  <div style={s.savedRating}><Star size={13} fill="#f59e0b" color="#f59e0b" />{gem.rating}</div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Settings */}
        <div style={s.rightCol}>
          <div className="glass-panel" style={s.section}>
            <div style={s.secHead}>
              <Shield size={18} color="var(--accent-emerald)" />
              <h3 style={s.secTitle}>Pengaturan Akun</h3>
            </div>
            {[
              { icon:<Bell size={16} />, label:'Notifikasi', desc:'Aktifkan push notification' },
              { icon:<Globe size={16} />, label:'Bahasa', desc:'Indonesia' },
              { icon:<Shield size={16} />, label:'Privasi', desc:'Atur visibilitas profil' },
            ].map((item, i) => (
              <div key={i} style={s.settingRow}>
                <div style={s.settingIcon}>{item.icon}</div>
                <div style={s.settingInfo}>
                  <div style={s.settingLabel}>{item.label}</div>
                  <div style={s.settingDesc}>{item.desc}</div>
                </div>
                <div style={s.settingArrow}>›</div>
              </div>
            ))}
          </div>

          <button className="glass-panel" style={s.logoutBtn} onClick={handleLogout}>
            <LogOut size={18} color="#ef4444" />
            <span style={{ color:'#ef4444', fontWeight:'600' }}>Keluar dari Akun</span>
          </button>
        </div>
      </div>
    </div>
  );
}

const s = {
  root: { display:'flex', flexDirection:'column', gap:'20px' },
  hero: { borderRadius:'20px', overflow:'hidden', position:'relative' },
  coverBg: { height:'100px', background:'linear-gradient(135deg, rgba(245,158,11,0.3) 0%, rgba(6,182,212,0.3) 100%)', position:'relative' },
  heroContent: { display:'flex', alignItems:'flex-start', gap:'20px', padding:'0 24px 24px', flexWrap:'wrap' },
  avatarWrap: { position:'relative', marginTop:'-40px' },
  avatar: { width:'80px', height:'80px', borderRadius:'50%', background:'linear-gradient(135deg,var(--accent-emerald),var(--accent-teal))', display:'flex', alignItems:'center', justifyContent:'center', fontSize:'2rem', fontWeight:'800', color:'white', border:'3px solid var(--bg-primary)' },
  cameraBtn: { position:'absolute', bottom:'2px', right:'2px', background:'rgba(245,158,11,0.9)', width:'24px', height:'24px', borderRadius:'50%', display:'flex', alignItems:'center', justifyContent:'center', cursor:'pointer' },
  heroInfo: { flex:1, paddingTop:'8px', minWidth:'200px' },
  userName: { fontSize:'1.5rem', fontWeight:'800', margin:'0 0 4px' },
  userEmail: { display:'flex', alignItems:'center', gap:'6px', color:'var(--text-muted)', fontSize:'0.85rem', marginBottom:'8px' },
  bio: { color:'var(--text-muted)', fontSize:'0.9rem', lineHeight:'1.5', margin:0 },
  nameInput: { width:'100%', marginBottom:'8px', fontSize:'1.1rem', fontWeight:'700' },
  bioInput: { width:'100%', background:'rgba(255,255,255,0.05)', border:'1px solid var(--glass-border)', borderRadius:'8px', color:'white', padding:'8px', fontSize:'0.9rem', resize:'none', height:'70px', outline:'none' },
  heroActions: { display:'flex', gap:'8px', paddingTop:'8px' },
  editBtn: { display:'flex', alignItems:'center', gap:'6px', padding:'8px 16px', fontSize:'0.85rem' },
  statsRow: { display:'flex', borderTop:'1px solid var(--glass-border)' },
  statItem: { flex:1, textAlign:'center', padding:'16px', borderRight:'1px solid var(--glass-border)' },
  statVal: { fontSize:'1.6rem', fontWeight:'800' },
  statLabel: { color:'var(--text-muted)', fontSize:'0.8rem', marginTop:'2px' },
  grid: { display:'grid', gridTemplateColumns:'1fr 340px', gap:'20px', alignItems:'start' },
  section: { borderRadius:'16px', padding:'20px' },
  secHead: { display:'flex', alignItems:'center', gap:'8px', marginBottom:'16px' },
  secTitle: { fontSize:'1rem', fontWeight:'700', margin:0, flex:1 },
  count: { background:'rgba(239,68,68,0.1)', color:'#ef4444', border:'1px solid rgba(239,68,68,0.2)', padding:'2px 10px', borderRadius:'50px', fontSize:'0.8rem', fontWeight:'600' },
  empty: { color:'var(--text-muted)', fontSize:'0.9rem', textAlign:'center', padding:'16px 0' },
  savedList: { display:'flex', flexDirection:'column', gap:'10px' },
  savedRow: { display:'flex', alignItems:'center', gap:'12px', background:'rgba(255,255,255,0.03)', borderRadius:'10px', overflow:'hidden' },
  savedImg: { width:'60px', height:'60px', backgroundSize:'cover', backgroundPosition:'center', flexShrink:0 },
  savedName: { fontWeight:'600', fontSize:'0.9rem' },
  savedLoc: { display:'flex', alignItems:'center', gap:'4px', color:'var(--text-muted)', fontSize:'0.8rem', marginTop:'3px' },
  savedRating: { display:'flex', alignItems:'center', gap:'4px', fontWeight:'700', fontSize:'0.85rem', marginLeft:'auto', paddingRight:'12px' },
  rightCol: { display:'flex', flexDirection:'column', gap:'12px' },
  settingRow: { display:'flex', alignItems:'center', gap:'12px', padding:'12px 0', borderBottom:'1px solid rgba(255,255,255,0.05)', cursor:'pointer' },
  settingIcon: { color:'var(--text-muted)' },
  settingInfo: { flex:1 },
  settingLabel: { fontWeight:'600', fontSize:'0.9rem' },
  settingDesc: { color:'var(--text-muted)', fontSize:'0.8rem', marginTop:'2px' },
  settingArrow: { color:'var(--text-muted)', fontSize:'1.4rem' },
  logoutBtn: { width:'100%', display:'flex', alignItems:'center', justifyContent:'center', gap:'10px', padding:'14px', borderRadius:'12px', cursor:'pointer', border:'none', background:'rgba(239,68,68,0.07)' },
};
