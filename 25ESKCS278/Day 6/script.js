 let studentName='rajshree';
        let studentBranch='cse';
        let studentrollno=23;
        let studentmarks= 90;
        
        let students =
            {
               name:'raj',
                branch:'cse',
                rollno:34,
                marks:98,
                skills:['html','css','js']
            } 
            console.log(studentName);
            console.log(studentBranch);
            console.log(studentrollno);
            console.log(studentmarks);
            console.log(students);

console.log(JSON.stringify(students));
let st='{"name":"raj","branch":"cse","rollno":34,"marks":98,"skills":["html","css","js"]}';
console.log(JSON.parse(st));

fetch('https://jsonplaceholder.typicode.com/todos/1')
      .then(response => response.json())
      .then(data => console.log(data));

