
const mongoose = require("mongoose");
require("dotenv").config({ path: "backend/.env" });
const Trainer = require("./backend/models/Trainer");
const Admin = require("./backend/models/Admin");

async function checkDB() {
  try {
    if (!process.env.MONGO_URI) {
        console.error("MONGO_URI is missing in .env");
        return;
    }
    await mongoose.connect(process.env.MONGO_URI);
    console.log("Connected to DB");

    const trainers = await Trainer.find({});
    console.log("TRAINERS count:", trainers.length);
    console.log("TRAINERS:", JSON.stringify(trainers, null, 2));

    const admins = await Admin.find({});
    console.log("ADMINS count:", admins.length);
    console.log("ADMINS:", JSON.stringify(admins, null, 2));

  } catch (error) {
    console.error("Error:", error);
  } finally {
    await mongoose.disconnect();
  }
}

checkDB();
