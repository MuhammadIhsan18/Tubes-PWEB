const terms = ['Tegenungan Waterfall', 'Bair Island', 'Tangkiling', 'Gua Hawang'];
async function searchImages() {
  for (const term of terms) {
    try {
      const res = await fetch(`https://commons.wikimedia.org/w/api.php?action=query&generator=search&gsrsearch=filetype:bitmap|drawing%20${encodeURIComponent(term)}&gsrnamespace=6&gsrlimit=1&prop=imageinfo&iiprop=url&format=json`);
      const data = await res.json();
      const pages = data.query?.pages;
      if (pages) {
        const page = Object.values(pages)[0];
        console.log(term, '=>', page.imageinfo[0].url);
      } else {
        console.log(term, '=> NOT FOUND');
      }
    } catch(e) { console.error(e) }
  }
}
searchImages();
