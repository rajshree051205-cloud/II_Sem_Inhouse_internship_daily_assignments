fetch('https://jsonplaceholder.typicode.com/todos/1')
      .then(response => response.json())
      .then(json => console.log(json))

      const h="hello rajshree this side";
      const lowercase =h.toLocaleLowerCase();
      const trim =h.trim();
      console.log(lowercase);
      console.log(trim);  