const titles = ['Sate_Maranggi', 'Bakso', 'Nasi_liwet', 'Mie_aceh', 'Gudeg', 'Rendang', 'Soto_Padang', 'Kopi'];
Promise.all(titles.map(title =>
  fetch(`https://id.wikipedia.org/w/api.php?action=query&prop=pageimages&titles=${title}&format=json&pithumbsize=800`)
  .then(r => r.json())
  .then(data => {
    const pages = data.query.pages;
    const pageId = Object.keys(pages)[0];
    return { title, img: pages[pageId].thumbnail ? pages[pageId].thumbnail.source : null };
  })
)).then(console.log);
