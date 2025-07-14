class Importer {

  content;

  constructor(jsonFile, callback) {
    const reader = new FileReader();
    reader.onload = (e) => {
      const text = e.target.result;
      this.content = JSON.parse(text);

      if (callback) {
        callback(this.content);
      }
    }

    reader.readAsText(jsonFile);
  }

}